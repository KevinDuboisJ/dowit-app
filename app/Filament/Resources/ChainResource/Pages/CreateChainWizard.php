<?php

namespace App\Filament\Resources\ChainResource\Pages;

use App\Enums\ChainActionType;
use App\Enums\EventEnum;
use App\Filament\Resources\ChainResource;
use App\Models\Campus;
use App\Models\Chain;
use App\Models\Space;
use App\Models\TaskType;
use App\Traits\HasFilamentTeamFields;
use Closure;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as ComponentAction;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\HtmlString;

class CreateChainWizard extends Page implements HasForms
{
    use InteractsWithForms;

    public ?Chain $record = null;

    public ?string $identifier = null;
    public ?string $description = null;
    public ?string $trigger_type = null;
    public ?int $trigger_task_type_id = null;
    public ?string $action = null;
    public ?array $actions = null;
    public ?array $ip_whitelist = null;
    public ?bool $is_active = null;
    public ?array $teams = null;

    protected static string $resource = ChainResource::class;
    protected static string $view = 'filament.pages.create-chain-wizard';

    public function mount(?Chain $record): void
    {
        $this->record = $record;

        if ($record?->actions) {
            $record->action = array_key_first($record->actions);
        }

        $this->form->fill(array_merge(
            $record?->attributesToArray() ?? [],
            [
                'teams' => $record?->teams?->pluck('id')->toArray() ?? [],
            ]
        ));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Configuratie')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->schema([
                            Section::make([
                                TextInput::make('identifier')
                                    ->label('Identificatie')
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('description')
                                    ->label('Omschrijving')
                                    ->columnSpan(1),

                                Select::make('trigger_type')
                                    ->label('Triggertype')
                                    ->options([
                                        'api' => 'API',
                                        EventEnum::TaskCompleted->value => EventEnum::TaskCompleted->getLabel(),
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state !== 'api') {
                                            $set('ip_whitelist', null);
                                        }

                                        if ($state !== EventEnum::TaskCompleted->value) {
                                            $set('trigger_task_type_id', null);
                                        }
                                    })
                                    ->columnSpan(1),

                                Select::make('trigger_task_type_id')
                                    ->label('Trigger taaktype')
                                    ->options(fn() => TaskType::pluck('name', 'id'))
                                    ->searchable()
                                    ->visible(fn(Get $get): bool => $get('trigger_type') === EventEnum::TaskCompleted->value)
                                    ->required(fn(Get $get): bool => $get('trigger_type') === EventEnum::TaskCompleted->value),

                                Select::make('action')
                                    ->label('Actie')
                                    ->options(ChainActionType::class)
                                    ->required()
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Set $set, $get, $record) {
                                        if ($record?->actions) {
                                            $set('action', array_key_first($record->actions));
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state === ChainActionType::CreateTask->name) {
                                            $set('actions.' . ChainActionType::CustomCode->name, null);
                                        }

                                        if ($state === ChainActionType::CustomCode->name) {
                                            $set('actions.' . ChainActionType::CreateTask->name, null);
                                        }
                                    }),
                            ])
                                ->columnSpan(6)
                                ->columns(2),

                            Section::make([
                                Forms\Components\TagsInput::make('ip_whitelist')
                                    ->label('IP Whitelist')
                                    ->placeholder('Nieuwe IP toevoegen')
                                    ->helperText('Alleen numerieke IPv4 adressen')
                                    ->rules([
                                        'array',
                                        'nullable',
                                        fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                            foreach ($value as $ip) {
                                                if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                                    $fail("'{$ip}' is geen geldig IPv4-adres.");
                                                }
                                            }
                                        },
                                    ])
                                    ->visible(fn(Get $get) => $get('trigger_type') === 'api')
                                    ->dehydratedWhenHidden(true),

                                HasFilamentTeamFields::customPageBelongsToTeamsField(
                                    tooltip: 'Teams waaraan de actie wordt toegewezen'
                                )
                                    ->required(fn(Get $get): bool => $get('action') !== ChainActionType::CustomCode->name)
                                    ->validationAttribute('teams'),

                                Toggle::make('is_active')
                                    ->label('Actief'),
                            ])
                                ->columnSpan(2),

                            Group::make()
                                ->schema([
                                    Section::make('Taak aanmaken')
                                        ->schema([
                                            Toggle::make('inherit_missing_from_trigger_task')
                                                ->label('Lege velden overnemen van oorspronkelijke taak')
                                                ->visible(fn(Get $get) => $this->isTaskCompletedTrigger($get))
                                                ->helperText('Als dit aan staat, worden velden die je leeg laat automatisch ingevuld met de waarden van de taak die deze ketting heeft getriggerd.')
                                                ->columnSpanFull()
                                                ->live(),

                                            TextInput::make('name')
                                                ->label('Naam')
                                                ->nullable()
                                                ->columnSpanFull()
                                                ->dehydrateStateUsing(fn($state) => blank($state) ? null : $state),

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
                                                ->columnSpanFull()
                                                ->dehydrateStateUsing(fn($state) => blank($state) ? null : $state),

                                            Select::make('task_type_id')
                                                ->label('Taaktype')
                                                ->options(fn() => TaskType::pluck('name', 'id'))
                                                ->searchable()
                                                ->nullable()
                                                ->live()
                                                ->dehydrateStateUsing(fn($state) => blank($state) ? null : $state),

                                            Select::make('campus_id')
                                                ->label('Campus')
                                                ->options(fn() => Campus::pluck('name', 'id'))
                                                ->searchable()
                                                ->nullable()
                                                ->live()
                                                ->dehydrateStateUsing(fn($state) => blank($state) ? null : $state),

                                            Select::make('space_id')
                                                ->label('Locatie')
                                                ->native(false)
                                                ->searchable(['name', '_spccode'])
                                                ->getSearchResultsUsing(
                                                    fn(string $search) => Space::query()
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
                                                ->preload()
                                                ->nullable()
                                                ->dehydrateStateUsing(fn($state) => blank($state) ? null : $state),

                                            Select::make('space_to_id')
                                                ->label('Bestemmingslocatie')
                                                ->native(false)
                                                ->searchable(['name', '_spccode'])
                                                ->getSearchResultsUsing(
                                                    fn(string $search) => Space::query()
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
                                                ->preload()
                                                ->nullable()
                                                ->visible(fn(Get $get) => $this->shouldShowSpaceToField($get))
                                                ->dehydrateStateUsing(fn($state) => blank($state) ? null : $state),
                                        ])
                                        ->columns(2),
                                ])
                                ->statePath('actions.' . ChainActionType::CreateTask->name)
                                ->visible(fn(Get $get) => $get('action') === ChainActionType::CreateTask->name)
                                ->columnSpan(6)
                                ->columns(2),

                            Group::make()
                                ->schema([
                                    Section::make('Script')
                                        ->schema([
                                            TextInput::make('CustomCode')
                                                ->label('Script')
                                                ->required(),
                                        ]),
                                ])
                                ->statePath('actions.' . ChainActionType::CustomCode->name)
                                ->visible(fn(Get $get) => $get('action') === ChainActionType::CustomCode->name)
                                ->columnSpan(6)
                                ->columns(2),
                        ])
                        ->columns(8),

                    Step::make('Bevestigen')
                        ->icon('heroicon-m-check-circle')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Placeholder::make('identifier')
                                        ->label('Identificatie')
                                        ->content(fn(Get $get): string => $get('identifier') ?? ''),

                                    Placeholder::make('description')
                                        ->label('Omschrijving')
                                        ->content(fn(Get $get): string => $get('description') ?? ''),

                                    Placeholder::make('trigger_type')
                                        ->label('Triggertype')
                                        ->content(fn(Get $get): string => $get('trigger_type') ?? ''),

                                    Placeholder::make('trigger_task_type_id')
                                        ->label('Trigger taaktype')
                                        ->content(fn(Get $get): string => TaskType::find($get('trigger_task_type_id'))?->name ?? '')
                                        ->visible(fn(Get $get): bool => $get('trigger_type') === EventEnum::TaskCompleted->value),

                                    Placeholder::make('action')
                                        ->label('Actie')
                                        ->content(fn(Get $get): string => $get('action')
                                            ? ChainActionType::fromCaseName($get('action'))->getLabel()
                                            : ''),

                                    Placeholder::make('ip_whitelist')
                                        ->label('IP Whitelist')
                                        ->content(function (Get $get): HtmlString {
                                            $ips = $get('ip_whitelist');

                                            if (! is_array($ips) || empty($ips)) {
                                                return new HtmlString('<em>Geen IP-adressen opgegeven.</em>');
                                            }

                                            $listItems = collect($ips)
                                                ->map(fn($ip) => "<li>{$ip}</li>")
                                                ->implode('');

                                            return new HtmlString("<ul class='list-disc ml-5'>{$listItems}</ul>");
                                        })
                                        ->visible(fn(Get $get) => $get('trigger_type') === 'api'),

                                    Placeholder::make('teams')
                                        ->label('Teams')
                                        ->content(function (Get $get): HtmlString {
                                            $teamIds = $get('teams') ?? [];

                                            if (empty($teamIds)) {
                                                return new HtmlString('<em>Geen teams geselecteerd.</em>');
                                            }

                                            $teamNames = \App\Models\Team::whereIn('id', $teamIds)->pluck('name')->toArray();

                                            $listItems = collect($teamNames)
                                                ->map(fn($name) => "<li>{$name}</li>")
                                                ->implode('');

                                            return new HtmlString("<ul class='list-disc ml-5'>{$listItems}</ul>");
                                        }),

                                    Placeholder::make('is_active')
                                        ->label('Actief')
                                        ->content(fn(Get $get): string => $get('is_active') ? 'Ja' : 'Nee'),
                                ])
                                ->columns(4),

                            Group::make()
                                ->schema([
                                    Section::make('Script')
                                        ->schema([
                                            Placeholder::make('Script')
                                                ->label('Script')
                                                ->content(fn(Get $get): string => $get('CustomCode') ?? ''),
                                        ]),
                                ])
                                ->statePath('actions.' . ChainActionType::CustomCode->name)
                                ->visible(fn(Get $get) => $get('action') === ChainActionType::CustomCode->name)
                                ->columnSpanFull(),

                            Group::make()
                                ->schema([
                                    Section::make('Taak aanmaken')
                                        ->schema([
                                            Placeholder::make('inherit_missing_from_trigger_task')
                                                ->label('Lege velden overnemen')
                                                ->content(fn(Get $get): string => $get('inherit_missing_from_trigger_task') ? 'Ja' : 'Nee')
                                                ->visible(fn(Get $get) => $this->isTaskCompletedTrigger($get)),

                                            Placeholder::make('name')
                                                ->label('Naam')
                                                ->content(fn(Get $get): string => $this->displayOptionalValue($get('name'))),

                                            Placeholder::make('description')
                                                ->label('Omschrijving')
                                                ->content(function (Get $get) {
                                                    $value = $get('description');

                                                    if (blank($value)) {
                                                        return new HtmlString('<em>Leeg</em>');
                                                    }

                                                    return new HtmlString(
                                                        tiptap_converter()->asHTML($value)
                                                    );
                                                }),

                                            Placeholder::make('task_type_id')
                                                ->label('Taaktype')
                                                ->content(fn(Get $get): string => $get('task_type_id')
                                                    ? (TaskType::find($get('task_type_id'))?->name ?? '')
                                                    : 'Leeg'),

                                            Placeholder::make('campus_id')
                                                ->label('Campus')
                                                ->content(fn(Get $get): string => $get('campus_id')
                                                    ? (Campus::find($get('campus_id'))?->name ?? '')
                                                    : 'Leeg'),

                                            Placeholder::make('space_id')
                                                ->label('Locatie')
                                                ->content(function (Get $get): string {
                                                    if (! $get('space_id')) {
                                                        return 'Leeg';
                                                    }

                                                    $space = Space::find($get('space_id'));

                                                    return $space ? "{$space->name} ({$space->_spccode})" : '';
                                                }),

                                            Placeholder::make('space_to_id')
                                                ->label('Bestemmingslocatie')
                                                ->content(function (Get $get): string {
                                                    if (! $get('space_to_id')) {
                                                        return 'Leeg';
                                                    }

                                                    $space = Space::find($get('space_to_id'));

                                                    return $space ? "{$space->name} ({$space->_spccode})" : '';
                                                })
                                                ->visible(fn(Get $get) => $this->shouldShowSpaceToReview($get)),
                                        ])
                                        ->columns(4),
                                ])
                                ->statePath('actions.' . ChainActionType::CreateTask->name)
                                ->visible(fn(Get $get) => $get('action') === ChainActionType::CreateTask->name)
                                ->columnSpanFull(),

                            HasFilamentTeamFields::creatorField(),
                        ])
                        ->columns(8),
                ])
                    ->nextAction(
                        fn(ComponentAction $action) => $action->label('Volgende')
                    )
                    ->contained(false)
                    ->submitAction(
                        Action::make('save')
                            ->label($this->record ? 'Wijzigingen opslaan' : 'Aanmaken')
                            ->submit('save')
                    ),
            ])
            ->model(Chain::class);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $teamIds = $data['teams'] ?? [];
        unset($data['teams']);

        if (isset($data['actions'][ChainActionType::CreateTask->name])) {
            $data['actions'][ChainActionType::CreateTask->name] = $this->normalizeCreateTaskAction(
                $data['actions'][ChainActionType::CreateTask->name]
            );
        }

        if ($this->record?->exists) {
            $chain = $this->record;
            $chain->update($data);
        } else {
            $chain = Chain::create($data);
        }

        $chain->teams()->sync($teamIds);

        Notification::make()
            ->title($this->record ? 'Opgeslagen' : 'Aangemaakt')
            ->success()
            ->send();

        $this->redirect(ChainResource::getUrl('index'));
    }

    protected function normalizeTriggerConditions(?array $conditions): array
    {
        return [
            'task' => [
                'only_with_locker' => (bool) data_get($conditions, 'task.only_with_locker', false),
            ],
        ];
    }

    protected function getFormModel(): string
    {
        return Chain::class;
    }

    protected function normalizeCreateTaskAction(array $action): array
    {
        foreach ($action as $key => $value) {
            if (is_string($value) && blank($value)) {
                $action[$key] = null;
            }
        }

        return $action;
    }

    protected function isTaskCompletedTrigger(Get $get): bool
    {
        return $get('../../trigger_type') === EventEnum::TaskCompleted->value
            || $get('trigger_type') === EventEnum::TaskCompleted->value;
    }

    protected function shouldShowSpaceToField(Get $get): bool
    {
        $taskTypeId = $get('task_type_id');

        return (string) $taskTypeId === '1';
    }

    protected function shouldShowSpaceToReview(Get $get): bool
    {
        return (string) $get('task_type_id') === '1' || filled($get('space_to_id'));
    }

    protected function displayOptionalValue(mixed $value): string
    {
        return filled($value) ? (string) $value : 'Leeg';
    }
}
