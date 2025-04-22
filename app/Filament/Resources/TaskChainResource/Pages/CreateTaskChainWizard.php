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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;
use Filament\Support\Assets\Js;

class CreateTaskChainWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TaskChainResource::class;
    protected static string $view = 'filament.pages.create-task-chain-wizard';

    public array $chainData = [];
    public array $steps = [];
    public array $validatedSteps = [];


    #[On('step-valid')]
    public function onStepValid($stepIndex)
    {
        $this->validatedSteps[] = $stepIndex;

        // Assume we wait for all steps (optional — or just one)
        if (count($this->validatedSteps) === count($this->steps)) {
            $this->validatedSteps = []; // reset
            $this->dispatchBrowserEvent('continue-wizard-step'); // trigger Alpine to proceed
        }
    }


    public static function boot(): void
    {
        FilamentAsset::register([
            Js::make('javascript', Vite::asset('resources/js/src/filament/app.js')),
        ]);
    }

    public function mount(): void
    {
        $this->form->fill([]);
    }

    public function getTitle(): string
    {
        return 'Nieuwe keten aanmaken';
    }

    protected function getFormSchema(): array
    {

        return [
            Wizard::make([
                Step::make('Configuratie')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        View::make('filament.pages.task-chain-builder')->viewData([
                            'steps' => $this->steps,
                        ]),
                    ])
                    ->afterValidation(fn($stepIndex, $goingToStepIndex, callable $proceed) => $this->validateStepForms($stepIndex, $proceed)),

                // Replace the static "Steps" step with our custom drag and drop view
                Step::make('Steps')
                    ->icon('heroicon-m-briefcase')
                    ->schema([
                        // Render our custom view that contains the two-panel drag and drop interface.
                        // View::make('filament.pages.task-chain-builder')->viewData([
                        //     'steps' => $this->,
                        // ]),

                    ]),

                Step::make('Bevestiging')
                    ->icon('heroicon-m-check-circle')
                    ->schema([
                        Forms\Components\Placeholder::make('summary')
                            ->content('Review your inputs and press Create to save.')
                    ]),
            ])
                ->contained(false)
                ->submitAction(
                    Action::make('create')
                        ->label('Create')
                        ->action('create')
                        ->keyBindings(['enter'])
                ),
        ];
    }

    public function validateStepForms(int $currentStep, callable $proceed)
    {
        // Only validate for the "Steps" step, adjust index as needed
        if ($currentStep === 1) {
            // Emit event to all task step forms to validate themselves
            $this->dispatch('validate-task-steps')
                ->to('task-step-form'); // Only targets components with this name
        }

        // Delay proceeding until step forms confirm they're valid
        $this->dispatch('proceed-after-validation', $proceed);
    }

    public function create()
    {
        $state = $this->form->getState();
        // Create the TaskChain record
        $taskChain = TaskChain::create($state['chainData']);

        // Process each step (the steps data is received as an array)
        foreach ($this->steps as $step) {
            // Here you can customize the creation process based on step type and configuration
            Task::create([
                ...$step,  // Contains the type and additional configuration from the step
                'task_chain_id' => $taskChain->id,
            ]);
        }

        Notification::make()
            ->title('Task Chain created!')
            ->success()
            ->send();

        $this->redirect(TaskChainResource::getUrl());
    }

    // public function toggleExpand($index)
    // {
    //     $this->steps[$index]['expanded'] = !($this->steps[$index]['expanded'] ?? false);
    // }

    // public function removeStep($index)
    // {
    //     array_splice($this->steps, $index, 1);
    // }


    // public function addStep($payload)
    // {
    //     $position = $payload['position'] ?? count($this->steps);

    //     array_splice($this->steps, $position, 0, [[
    //         'type' => $payload['type'],
    //         'expanded' => true,
    //         'task_name' => '',
    //         'task_description' => '',
    //     ]]);
    // }

    protected function getFormModel(): string
    {
        return TaskChain::class;
    }
}
