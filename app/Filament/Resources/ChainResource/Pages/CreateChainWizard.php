<?php

namespace App\Filament\Resources\ChainResource\Pages;

use App\Enums\ChainActionType;
use App\Filament\Resources\ChainResource;
use App\Models\Chain;
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
use Filament\Forms\Components\Select;
use FilamentTiptapEditor\TiptapEditor;
use App\Models\Space;
use Filament\Forms\Get;
use Filament\Forms\Components\TextInput;
use App\Models\TaskType;
use App\Models\Campus;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Illuminate\Support\HtmlString;
use App\Traits\HasFilamentTeamFields;
use FilamentTiptapEditor\Enums\TiptapOutput;

use Closure;

class CreateChainWizard extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * If Filament routes you here with an existing Chain ID, this will be set.
     * Otherwise (creating new), it stays null.
     */
    public ?Chain $record = null;

    /**
     * Holds “chain” data (e.g. name, description). Bound to the wizard form.
     */
    public ?string $identifier = null;
    public ?string $description = null;
    public ?string $trigger_type = null;
    public ?string $action = null;
    public ?array  $actions = null;
    public ?array  $ip_whitelist = null;
    public ?bool   $is_active = null;
    public ?array  $teams = null;


    protected static string $resource = ChainResource::class;
    protected static string $view     = 'filament.pages.create-chain-wizard';

    /**
     * mount(): runs when Filament instantiates this Page. If $recordId was passed
     * (i.e. we’re editing), load the existing Chain. Also register
     * our custom JS asset here.
     */

    public function mount(?Chain $record): void
    {

        if ($record?->actions) {
            $key = array_key_first($record->actions);
            $record->action = $key;
        }

        // Register the compiled JS (Alpine + SortableJS) so Filament loads it.
        // FilamentAsset::register([
        //     Js::make('sort.js', Vite::asset('resources/js/src/filament/sort.js')),
        // ]);

        // If a record‐ID was provided by Filament (edit mode), load it from DB.


        //     $this->actions = [
        //     ['id' => 99, 'type' => 'email', 'name' => 'Test Email', 'expanded' => false],
        //     ['id' => 100, 'type' => 'approval', 'name' => 'Test Approval', 'expanded' => false],
        // ];

        // Pre‐fill the Filament form with data so the “Review” step sees them.
        $this->form->fill(array_merge(
            $record->attributesToArray(),
            [
                'teams' => $record->teams->pluck('id')->toArray(),
            ]
        ));
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
                            Forms\Components\TextInput::make('identifier')
                                ->label('Identificatie')
                                ->required(),

                            Forms\Components\TextInput::make('description')
                                ->label('Omschrijving'),

                            Forms\Components\Select::make('trigger_type')
                                ->label('Triggertype ')
                                ->options([
                                    'api' => 'API',
                                    'internal' => 'Intern',
                                ])
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state === 'internal') {
                                        $set('ip_whitelist', null);
                                    }
                                })
                                ->required()
                                ->live(),

                            // Forms\Components\KeyValue::make('trigger_conditions')
                            //     ->label('Conditions')
                            //     ->keyLabel('field')
                            //     ->valueLabel('value')
                            //     ->required(),

                            Forms\Components\Select::make('action')
                                ->label('Actie')
                                ->options(ChainActionType::class)
                                ->required()
                                // load existing value on edit
                                ->afterStateHydrated(function ($set, $get, $record) {
                                    if ($record?->actions) {
                                        $key = array_key_first($record->actions);
                                        $set('action', $key);
                                    }
                                })
                                // when the user changes the action, clear the class field
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state === ChainActionType::CreateTask->name) {
                                        $set('actions.' . ChainActionType::CustomCode->name, null); // clear custom code data, preserve CreateTask
                                    } elseif ($state === ChainActionType::CustomCode->name) {
                                        $set('actions.' . ChainActionType::CreateTask->name, null); // clear task fields only
                                    }
                                })
                                // but we do NOT want Filament to try to save this directly
                                ->dehydrated(false)
                                ->live(),


                        ])->extraAttributes(['class' => 'h-full'])->columnSpan(6)->columns(2),

                        Forms\Components\Section::make([
                            Forms\Components\TagsInput::make('ip_whitelist')
                                ->label('IP Whitelist')
                                ->placeholder('Nieuwe IP toevoegen')
                                ->helperText('Alleen numerieke IPv4 adressen')
                                ->rules([
                                    'array',
                                    'nullable',
                                    fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                        foreach ($value as $ip) {
                                            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                                $fail("'$ip' is geen geldig IPv4-adres.");
                                            }
                                        }
                                    },
                                ])
                                ->visible(fn($get) => $get('trigger_type') === 'api')
                                ->dehydratedWhenHidden(true),

                            HasFilamentTeamFields::customPageBelongsToTeamsField(tooltip: 'Teams waaraan de actie wordt toegewezen'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Actief'),

                        ])->columnSpan(2),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    TextInput::make('name')
                                        ->label('Naam')
                                        ->nullable()
                                        ->required(),

                                    TiptapEditor::make('description')
                                        ->label('Omschrijving')
                                        ->placeholder('Voer hier een omschrijving in...')
                                        ->tools([
                                            'bold',
                                            'italic',
                                            'link',
                                            'bullet-list',
                                            'ordered-list',
                                        ])
                                        ->disableFloatingMenus()
                                        ->disableBubbleMenus()
                                        ->nullable()
                                        ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                                        ->maxContentWidth('full')
                                        ->columnSpanFull(),

                                    Select::make('task_type_id')
                                        ->label('Taaktype')
                                        ->options(fn() => TaskType::pluck('name', 'id'))
                                        ->required()
                                        ->live(),

                                    Select::make('campus_id')
                                        ->label('Campus')
                                        ->options(fn() => Campus::pluck('name', 'id'))
                                        ->required()
                                        ->live(),

                                    Select::make('space_id')
                                        ->label('Locatie')
                                        ->native(false)
                                        ->searchable(['name', '_spccode'])
                                        ->getSearchResultsUsing(
                                            fn(string $search) =>
                                            Space::query()
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere('_spccode', 'like', "%{$search}%")
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(fn($space) => [
                                                    $space->id => "{$space->name} ({$space->_spccode})",
                                                ])
                                        )
                                        ->getOptionLabelUsing(
                                            fn($value): ?string => ($space = Space::find($value))
                                                ? "{$space->name} ({$space->_spccode})"
                                                : null
                                        )
                                        ->preload(),

                                    Select::make('space_to_id')
                                        ->label('Bestemmingslocatie')
                                        ->native(false)
                                        ->searchable(['name', '_spccode'])
                                        ->getSearchResultsUsing(
                                            fn(string $search) =>
                                            Space::query()
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere('_spccode', 'like', "%{$search}%")
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(fn($space) => [
                                                    $space->id => "{$space->name} ({$space->_spccode})",
                                                ])
                                        )
                                        ->required()
                                        ->visible(fn(Get $get): bool => $get('task_type_id') === '1'),
                                ])

                            ])
                            ->statePath('actions.' . ChainActionType::CreateTask->name)
                            ->visible(fn(Get $get) => $get('action') === ChainActionType::CreateTask->name)
                            ->columnSpan(6)
                            ->columns(2),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    Forms\Components\TextInput::make('CustomCode')
                                        ->label('Aangepaste codeklasse')
                                        ->required()
                                ])

                            ])
                            ->statePath('actions.' . ChainActionType::CustomCode->name)
                            ->visible(fn(Get $get) => $get('action') === ChainActionType::CustomCode->name)
                            ->columnSpan(6)
                            ->columns(2),




                    ])->columns(8),

                // Step::make('Acties')
                //     ->icon('heroicon-m-list-bullet')
                //     ->schema([
                //         // ── Custom two‐pane builder ──
                //         ViewField::make('actions')->view('filament.pages.chain-builder', ['actions' => $this->actions])->columnSpanFull(),

                //     ])->afterValidation(fn() => dd($this->actions)),

                //
                Step::make('Bevestigen')
                    ->icon('heroicon-m-check-circle')
                    ->schema([
                        Forms\Components\Section::make()->schema([
                            Placeholder::make('identifier')
                                ->label('Identificatie: ')
                                ->content(fn(Get $get): string => $get('identifier') ?? '')
                                ->extraAttributes(['class' => 'text-green-700']),

                            Placeholder::make('description')
                                ->label('Omschrijving: ')
                                ->content(fn(Get $get): string => $get('description') ?? '')
                                ->extraAttributes(['class' => 'text-green-700']),


                            Placeholder::make('trigger_type')
                                ->label('Triggertype: ')
                                ->content(fn(Get $get): string => $get('trigger_type') ?? '')
                                ->extraAttributes(['class' => 'text-green-700']),

                            Placeholder::make('action')
                                ->label('Actie: ')
                                ->content(fn(Get $get): string => $get('action') ? ChainActionType::fromCaseName($get('action'))->getLabel() : '')
                                ->extraAttributes(['class' => 'text-green-700']),

                            Placeholder::make('ip_whitelist')
                                ->label('IP Whitelist')
                                ->content(function (Get $get): HtmlString {
                                    $ips = $get('ip_whitelist');

                                    if (!is_array($ips) || empty($ips)) {
                                        return new HtmlString('<em>Geen IP-adressen opgegeven.</em>');
                                    }

                                    $listItems = collect($ips)
                                        ->map(fn($ip) => "<li>{$ip}</li>")
                                        ->implode('');

                                    return new HtmlString("<ul class='list-disc ml-5'>{$listItems}</ul>");
                                })
                                ->extraAttributes(['class' => 'text-green-700'])
                                ->visible(fn($get) => $get('trigger_type') === 'api'),

                            Placeholder::make('teams')
                                ->label('Teams')
                                ->content(function (Get $get): HtmlString {
                                    $teamIds = $get('teams');

                                    $teamNames = \App\Models\Team::whereIn('id', $teamIds)->pluck('name')->toArray();

                                    $listItems = collect($teamNames)
                                        ->map(fn($name) => "<li>{$name}</li>")
                                        ->implode('');

                                    return new HtmlString("<ul class='list-disc ml-5'>{$listItems}</ul>");
                                })
                                ->extraAttributes(['class' => 'text-green-700']),


                            Placeholder::make('is_active')
                                ->label('Actief: ')
                                ->content(fn(Get $get): string => $get('is_active') ? 'Ja' : 'Nee')
                                ->extraAttributes(['class' => 'text-green-700']),
                        ])->columns(4),

                        Forms\Components\Group::make()->schema([
                            Forms\Components\Section::make()->schema([
                                Placeholder::make('CustomCode')
                                    ->label('Codeklasse: ')
                                    ->content(fn(Get $get): string => $get(ChainActionType::CustomCode->name) ?? '')
                                    ->visible(fn($state) => filled($state))
                                    ->extraAttributes(['class' => 'text-green-700']),
                            ])
                        ])
                            ->statePath('actions.' . ChainActionType::CustomCode->name)
                            ->visible(fn(Get $get) => $get('action') === ChainActionType::CustomCode->name)
                            ->columnSpanFull()
                            ->columns(4),

                        Forms\Components\Group::make()->schema([
                            Forms\Components\Section::make()->schema([
                                Placeholder::make('name')
                                    ->label('Taaknaam: ')
                                    ->content(fn(Get $get): string => $get('name') ?? '')
                                    ->extraAttributes(['class' => 'text-green-700']),

                                Placeholder::make('description')
                                    ->label('Omschrijving taak: ')
                                    ->content(fn(Get $get) => new HtmlString(
                                        tiptap_converter()->asHTML($get('description') ?? '')
                                    ))
                                    ->extraAttributes(['class' => 'text-green-700']),

                                Placeholder::make('task_type_id')
                                    ->label('Taaktype: ')
                                    ->content(fn(Get $get): string => TaskType::find($get('task_type_id'))?->name ?? '')
                                    ->extraAttributes(['class' => 'text-green-700']),

                                Placeholder::make('campus_id')
                                    ->label('Campus: ')
                                    ->content(fn(Get $get): string => Campus::find($get('campus_id'))?->name ?? '')
                                    ->extraAttributes(['class' => 'text-green-700']),

                                Placeholder::make('space_id')
                                    ->label('Locatie: ')
                                    ->content(function (Get $get) {
                                        $space = \App\Models\Space::find($get('space_id'));
                                        return $space ? "{$space->name} ({$space->_spccode})" : '';
                                    })
                                    ->extraAttributes(['class' => 'text-green-700']),

                                Placeholder::make('space_to_id')
                                    ->label('Bestemming: ')
                                    ->content(function (Get $get) {
                                        $space = Space::find($get('space_to_id'));
                                        return $space ? "{$space->name} ({$space->_spccode})" : '';
                                    })
                                    ->visible(fn(Get $get): bool => $get('task_type_id') === '1')
                                    ->extraAttributes(['class' => 'text-green-700']),
                            ])
                                ->columns(4)

                        ])
                            ->statePath('actions.' . ChainActionType::CreateTask->name)
                            ->visible(fn(Get $get) => $get('action') === ChainActionType::CreateTask->name)
                            ->columnSpanFull(),


                    ])
                    ->columns(8),


                HasFilamentTeamFields::creatorField(),
            ])
                ->nextAction(
                    fn(ComponentAction $action) => $action->label('Volgende'),
                )
                ->contained(false)
                ->submitAction(
                    Action::make('save')
                        ->label($this->record ? 'Wijzigingen opslaan' : 'Aanmaken')
                        ->submit('create')
                ),

        ])->model(Chain::class);
    }

    /**
     * Persist (create or update) the Chain
     */
    public function save(): void
    {
        $data = $this->form->getState();
        $chain = null;

        // Extract and remove teams from $data
        $teamIds = $data['teams'] ?? [];
        unset($data['teams']);

        if ($this->record) {
            // ── Update existing Chain ──
            $chain = $this->record;
            $chain->update($data);
        } else {
            // Create a new Chain
            $chain = Chain::create($data);
        }

        // Sync teams manually
        $chain->teams()->sync($teamIds);

        Notification::make()
            ->title($this->record ? 'Opgeslagen' : 'Aangemaakt')
            ->success()
            ->send();

        // Redirect to the Resource index
        $this->redirect(ChainResource::getUrl('index'));
    }

    /**
     * Let Filament know that our “data” is using the Chain model
     * (mainly for correct form binding). We manually populated $this->data
     * in mount(), so nothing else is needed here.
     */
    protected function getFormModel(): string
    {
        return Chain::class;
    }
}
