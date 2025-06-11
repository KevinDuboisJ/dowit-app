<?php

namespace App\Filament\Resources\TaskChainResource\Pages;

use App\Filament\Resources\TaskChainResource;
use App\Models\Task;
use App\Models\TaskChain;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;
use Filament\Support\Assets\Js;
use Filament\Forms\Form;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Actions\Action as ComponentAction;

class CreateTaskChainWizard extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * If Filament routes you here with an existing TaskChain ID, this will be set.
     * Otherwise (creating new), it stays null.
     */
    public ?TaskChain $record = null;

    /**
     * Holds “chain” data (e.g. name, description). Bound to the wizard form.
     */
    public ?string $name = null;
    public ?string $trigger_source = null;
    public ?string $trigger_event = null;
    public ?int $space_id = null;
    public ?string $description = null;
    public ?string $conditions = null;
    public ?bool $is_active = null;
    public ?array $actions = [];


    protected static string $resource = TaskChainResource::class;
    protected static string $view     = 'filament.pages.create-task-chain-wizard';

    /**
     * mount(): runs when Filament instantiates this Page. If $recordId was passed
     * (i.e. we’re editing), load the existing TaskChain and its tasks. Also register
     * our custom JS asset here.
     */

    public function mount(?int $record = null): void
    {
        // 1️⃣ Register the compiled JS (Alpine + SortableJS) so Filament loads it.

        FilamentAsset::register([
            Js::make('sort.js', Vite::asset('resources/js/src/filament/sort.js')),
        ]);

        // If a record‐ID was provided by Filament (edit mode), load it from DB.
        if ($record) {
            $this->record = TaskChain::findOrFail($record);

            // Fill data from the existing record.
            $this->name       = $this->record->name;
            $this->description = $this->record->description;
            $this->actions = [];
        }

        //     $this->actions = [
        //     ['id' => 99, 'type' => 'email', 'name' => 'Test Email', 'expanded' => false],
        //     ['id' => 100, 'type' => 'approval', 'name' => 'Test Approval', 'expanded' => false],
        // ];

        // Pre‐fill the Filament form with data so the “Review” step sees them.
        $this->form->fill();
    }

    /**
     * Build the Filament wizard. We have two actions:
     *  1) “Configuration” – chain info + our drag‐and‐drop view
     *  2) “Review & Submit” – a placeholder that shows a summary
     */
    public function form(Form $form): Form
    {

        return $form->schema([
            Wizard::make([
                Step::make('Configuratie')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Forms\Components\Section::make([
                            Forms\Components\TextInput::make('name')
                                ->label('Ketting naam')
                                ->required(),
                            Forms\Components\Select::make('trigger_source')
                                ->label('Triggerbron')
                                ->options([
                                    'api' => 'API',
                                ])
                                ->required()
                                ->live(),
                            Forms\Components\Select::make('trigger_event')
                                ->label('Triggergebeurtenis')
                                ->options([
                                    'create' => 'Aanmaken',
                                ])
                                ->required(),
                            Forms\Components\Select::make('space_id')
                                ->label('locatie')
                                ->native(false)
                                ->relationship('space', 'name')
                                ->searchable(['name', '_spccode'])
                                ->getSearchResultsUsing(function (string $search) {
                                    return \App\Models\Space::query()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('_spccode', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($space) => [
                                            $space->id => "{$space->name} ({$space->_spccode})",
                                        ]);
                                })
                                ->reactive()
                                ->visible(fn(\Filament\Forms\Get $get) => $get('trigger_source') === 'internal')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('omschrijving')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])->columnSpan(6)->columns(2),

                        Forms\Components\Group::make()->schema([
                            Forms\Components\Section::make([
                                Forms\Components\Textarea::make('conditions')
                                    ->label('Voorwaarden (JSON format)')
                                    ->rows(5)
                                    ->helperText('Voorbeeld: {"campus_id": 3, "space_id":5}')
                                    ->columnSpan(2),
                            ]),

                            Forms\Components\Section::make([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Actief')
                                    ->default(true)
                                    ->accepted(),
                            ])
                        ])
                            ->columnSpan(2)

                    ])
                    ->columns(8),

                Step::make('Acties')
                    ->icon('heroicon-m-list-bullet')
                    ->schema([
                        // ── Custom two‐pane builder ──
                        ViewField::make('actions')->view('filament.pages.task-chain-builder', ['actions' => $this->actions])->columnSpanFull(),

                    ])->afterValidation(fn() => dd($this->actions)),

                //
                Step::make('Review & Submit')
                    ->icon('heroicon-m-check-circle')
                    ->schema([
                        Forms\Components\Placeholder::make('summary')
                            ->label('Review & Confirm')
                            ->content(fn() => logger('Review')),
                    ])
            ])
                ->nextAction(
                    fn(ComponentAction $action) => $action->label('Next step'),
                )
                ->contained(false)
                ->submitAction(
                    Action::make('save')
                        ->label($this->record ? 'Update' : 'Create')
                        ->action(fn() => $this->saveTaskChain())
                        ->keyBindings(['enter'])
                ),

        ]);
    }


    /**
     * Called when the “Next” button is clicked on the first wizard step.
     * We can perform any server‐side validation here. For now, we just proceed.
     */
    public function onStepValid(int $currentStep, callable $proceed): void
    {
        // If you wanted to dispatch validation to per‐step forms, do it here.
        // For now, we simply allow moving to the next step:
        $proceed();
    }

    /**
     * Persist (create or update) the TaskChain + its Tasks into the database.
     */
    protected function saveTaskChain(): void
    {
        if ($this->record) {
            // ── Update existing TaskChain ──
            $taskChain = $this->record;
            $taskChain->update([
                'name'        => $this->data['name'],
                'description' => $this->data['description'],
            ]);

            // Delete any tasks that were removed by the user
            $incomingIds = collect($this->data['actions'])->pluck('id')->filter(fn($id) => is_int($id))->all();
            Task::where('task_chain_id', $taskChain->id)
                ->whereNotIn('id', $incomingIds)
                ->delete();
        } else {
            // ── Create a brand‐new TaskChain ──
            $taskChain = TaskChain::create([
                'name'        => $this->data['name'],
                'description' => $this->data['description'],
            ]);
        }

        // Upsert each step in its correct order
        foreach ($this->actions as $sortOrder => $step) {
            $attributes = [
                'task_chain_id' => $taskChain->id,
                'type'          => $step['type'],
                'name'          => $step['name'],
                'sort_order'    => $sortOrder,
                // If you store extra JSON/config data in the Task model, add it here:
                // 'config_json' => json_encode($step['config'] ?? []),
            ];

            // If this step already had a numeric DB ID, update. Otherwise, create new.
            if (isset($step['id']) && is_int($step['id'])) {
                Task::findOrFail($step['id'])->update($attributes);
            } else {
                Task::create($attributes);
            }
        }

        Notification::make()
            ->title($this->record ? 'Opgeslagen' : 'Aangemaakt')
            ->success()
            ->send();

        // Redirect to the Resource index
        $this->redirect(TaskChainResource::getUrl('index'));
    }

    /**
     * Let Filament know that our “data” is using the TaskChain model
     * (mainly for correct form binding). We manually populated $this->data
     * in mount(), so nothing else is needed here.
     */
    protected function getFormModel(): string
    {
        return TaskChain::class;
    }
}
