<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskPlannerResource\Pages;
use App\Models\TaskPlanner;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Enums\TaskPlannerFrequency;
use App\Enums\ApplyOnHoliday;
use App\Enums\TaskPlannerAction;
use App\Enums\DaysOfWeek;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Enums\TeamRole;
use App\Filament\Components\PatientAutocomplete;
use App\Models\Space;
use Illuminate\Support\Carbon;
use App\Services\TaskPlannerService;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Set;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use App\Models\User;
use App\Models\Asset;
use App\Models\PATIENTLIST\Visit;
use Filament\Forms\Components\Grid;
use App\Services\TaskAssignmentService;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Group;
use App\Traits\HasFilamentTeamFields;
use App\Models\Team;
use Illuminate\Support\Arr;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TaskType;

class TaskPlannerResource extends Resource
{
    protected static ?string $model = TaskPlanner::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'Taakplanner';

    protected static ?string $pluralModelLabel = 'Ingeplande taken';

    protected static ?string $navigationGroup = 'Taakconfigurator';

    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make([
                Select::make('task_type_id')
                    ->label('Taaktype')
                    ->relationship(
                        name: 'taskType',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, Get $get): Builder {

                            $user = Auth::user();

                            if ($user->isSuperAdmin()) return $query;

                            return $query->byAvailableToTeams($user->getTeamIds());
                        }
                    )
                    ->required()
                    ->disabled(fn(Get $get): bool => blank($get('ownerTeams')))
                    ->helperText(
                        fn(Get $get): ?string =>
                        blank($get('ownerTeams'))
                            ? 'Kies eerst een eigenaarsteam.'
                            : null
                    )
                    ->live()
                    ->rule(function (Get $get) {
                        return function (string $attribute, mixed $value, \Closure $fail) use ($get) {

                            $user = Auth::user();

                            if (! $value) {
                                return;
                            }

                            $isAllowed = TaskType::query()
                                ->whereKey($value)
                                ->byAvailableToTeams($user->getTeamIds())
                                ->exists() || $user->isSuperAdmin();

                            if (! $isAllowed) {
                                $fail('Het gekozen eigenaarsteam mag dit taaktype niet aanvragen.');
                            }
                        };
                    })
                    ->afterStateUpdated(function (Select $component, Set $set, ?string $state, Get $get) {
                        $taskType = $component->getSelectedRecord();

                        if (! $taskType) {
                            return;
                        }

                        $set('name', $taskType->name);

                        if (! TaskTypeEnum::tryFrom((int) $state)?->isPatientTransport()) {
                            $set('visit_id', null);
                        }
                    }),

                Select::make('campus_id')
                    ->label('Campus')
                    ->relationship('campus', 'name')
                    ->required()
                    ->live(),

                TextInput::make('name')
                    ->label('Onderwerp')
                    ->nullable()
                    ->required()
                    ->extraAttributes([
                        'wire:loading.class' => 'pointer-events-none opacity-60',
                        'wire:target' => 'data.task_type_id',
                    ]),

                DateTimePicker::make('next_run_at')
                    ->label('Uitvoerstijdstip')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->timezone('Europe/Brussels')
                    ->displayFormat('d/m/Y H:i')
                    ->format('Y-m-d H:i:s')
                    ->default(now()->addMinute())
                    ->dehydrateStateUsing(fn($state) => Carbon::parse($state)->format('Y-m-d H:i:s'))
                    ->hint(
                        new HtmlString(view('filament.components.hint-icon', [
                            'tooltip' => 'Voor patiëntentransport is dit het tijdstip op locatie',
                        ])->render())
                    ),

                TiptapEditor::make('description')
                    ->label('Omschrijving')
                    ->tools([
                        'bold',
                        'italic',
                        'link',
                        'bullet-list',
                        'ordered-list',
                    ])
                    ->disableFloatingMenus()
                    ->disableBubbleMenus()
                    ->placeholder('Voer hier een omschrijving in...')
                    ->nullable()

                    ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                    ->maxContentWidth('full')
                    ->columnSpanFull(),

                PatientAutocomplete::make('visit_id')
                    ->label('Patiënt')
                    ->required()
                    ->visible(
                        fn(Get $get): bool => TaskTypeEnum::tryFrom((int) $get('task_type_id'))?->isPatientTransport() ?? false
                    )
                    ->afterStateHydrated(function ($component, $state) {
                        if ($state) {
                            $patient = Visit::with(['patient', 'bed.room'])->byIsAdmitted()->find($state);
                            $component->state($patient);
                        }
                    })
                    ->afterStateUpdated(function ($state, Set $set) {

                        if (!$state) {
                            $set('space_to_id', null);
                            $set('space_id', null);
                            return;
                        }

                        $set('space_to_id', 2859);
                        $roomNumber = $state['bed']['room']['number'] ?? null;

                        if (! $roomNumber) {
                            return;
                        }

                        // // Load visit with bed/room/space
                        $space = Space::where('name', 'like', '%' . $roomNumber)->first();

                        if ($space) {
                            $set('space_id', $space->id);
                        }
                    })
                    ->dehydrateStateUsing(fn($state) => $state['id'])
                    ->live(),

                Select::make('space_id')
                    ->label('locatie')
                    ->relationship(
                        name: 'space',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, $get, ?string $search) {

                            $search = trim((string) $search);

                            if (TaskTypeEnum::tryFrom((int) $get('task_type_id'))?->isPatientTransport() && blank($search)) {
                                $query->whereIn('id', [2069, 2859, 1977, 2149, $get('space_id')]);
                            } else {
                                $query->byUserInput($search);
                            }

                            return $query->limit(50);
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn(Model $space) => "{$space->name} ({$space->_spccode})")
                    ->searchable(['name', '_spccode'])
                    ->preload()
                    ->extraAttributes([
                        'wire:target' => 'data.visit_id',
                    ])
                    ->live(),

                Select::make('space_to_id')
                    ->label('Bestemmingslocatie')
                    ->options(function ($livewire, $get) {

                        $ids = [];

                        if (TaskTypeEnum::tryFrom((int) $get('task_type_id'))?->isPatientTransport()) {
                            $ids = [2069, 2859, 1977, 2149, $get('space_id')];
                        }

                        if ($ids) {
                            return Space::whereIn('id', $ids)->select(['id', DB::raw("CONCAT(name, ' (', _spccode, ')') as name")])->pluck('name', 'id')->toArray();
                        }

                        return [];
                    })

                    ->getSearchResultsUsing(function (string $search) {
                        $search = trim($search);

                        $query = Space::query()->byUserInput($search);

                        return $query
                            ->limit(3)
                            ->select([
                                'id',
                                DB::raw("CONCAT(name, ' (', _spccode, ')') as name")
                            ])
                            ->pluck('name', 'id');
                    })

                    ->getOptionLabelUsing(function ($state) {
                        $space = $state
                            ? Space::select('name', '_spccode')->find($state)
                            : null;

                        return $space
                            ? trim("{$space->name} ({$space->_spccode})", ' ()')
                            : null;
                    })

                    ->searchable()
                    ->preload()
                    ->visible(
                        fn(Get $get): bool =>
                        TaskTypeEnum::tryFrom((int) $get('task_type_id'))?->isPatientTransport() ?? false
                    )
                    ->extraAttributes([
                        'wire:target' => 'data.visit_id',
                    ])
                    ->live(),

                // TextInput doesn't automatically convert an array to a string, unlike the TextColumn. To Solve this i use formatStateUsing
            ])->columnSpan(6)->columns(2),

            Group::make()->schema([
                Section::make([
                    Grid::make()->schema([

                        Select::make('frequency')
                            ->label('Frequentie')
                            ->options(TaskPlannerFrequency::class)
                            ->nullable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Select $component, Set $set, Get $get) {
                                // Clear previous interval state so the new UI mounts clean
                                $set('interval', []);
                            }),

                        Group::make()
                            ->schema(fn(Get $get): array => match ($get('frequency')) {

                                // This field allows to empty the interval when a new frequency is set. Without this filament will ignore interval and leave it with the previous database value
                                TaskPlannerFrequency::Daily->name,
                                TaskPlannerFrequency::Monthly->name,
                                TaskPlannerFrequency::Quarterly->name,
                                TaskPlannerFrequency::Weekdays->name => [
                                    Hidden::make('interval')
                                        ->dehydrated(),
                                ],

                                TaskPlannerFrequency::SpecificDays->name => [
                                    Select::make('interval')
                                        ->label('Dagen')
                                        ->options(DaysOfWeek::class)
                                        ->multiple()
                                        ->required()
                                        ->columnSpan(1), // Zorgen dat het maar 1 kolom inneemt
                                ],

                                TaskPlannerFrequency::Weekly->name => [
                                    Select::make('interval')
                                        ->label('Dag')
                                        ->options(DaysOfWeek::class)
                                        ->required()
                                        ->columnSpan(1),
                                ],

                                TaskPlannerFrequency::EachXDay->name => [
                                    TextInput::make('interval')
                                        ->label('Interval')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(1),
                                ],

                                TaskPlannerFrequency::EachXMonth->name => [
                                    TextInput::make('interval')
                                        ->label('Interval')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(1),
                                ],

                                TaskPlannerFrequency::WeekdayInMonth->name => [
                                    Select::make('interval.week_number')
                                        ->label('Weeknummer')
                                        ->options([
                                            1 => '1e week',
                                            2 => '2e week',
                                            3 => '3e week',
                                            4 => '4e week',
                                        ])
                                        ->required()
                                        ->columnSpan(1),

                                    Select::make('interval.day_of_week')
                                        ->label('Weekdag')
                                        ->options([
                                            'Monday' => 'Maandag',
                                            'Tuesday' => 'Dinsdag',
                                            'Wednesday' => 'Woensdag',
                                            'Thursday' => 'Donderdag',
                                            'Friday' => 'Vrijdag',
                                            'Saturday' => 'Zaterdag',
                                            'Sundayf' => 'Zondag',
                                        ])
                                        ->required()
                                        ->columnSpan(1),
                                ],

                                default => [],
                            })
                            ->live()
                            ->key('dynamicFields'),
                    ])->columns(2),

                    Flatpickr::make('excluded_dates')
                        ->label('Uitgesloten datums')
                        ->multiplePicker()
                        ->displayFormat(' j F Y')
                        ->hint(
                            new HtmlString(view('filament.components.hint-icon', [
                                'tooltip' => 'De planner slaat deze datums over en plant geen taken op deze dagen',
                            ])->render())
                        ),

                    Select::make('assets')
                        ->label('Bestanden')
                        ->getSearchResultsUsing(fn(string $search): array => Asset::where(function ($query) use ($search) {
                            $query->where('name', 'like', "{$search}%")
                                ->orWhere('link', 'like', "{$search}%");
                        })->limit(50)->get()->pluck('name', 'id')->toArray())
                        ->getOptionLabelsUsing(fn(array $values): array => Asset::whereIn('id', $values)->get()->pluck('name', 'id')->toArray())
                        ->multiple(),

                    Select::make('tags')
                        ->label('Tags')
                        ->multiple()
                        ->relationship('tags', 'name')
                        ->preload()
                        ->live(),

                    Select::make('on_holiday')
                        ->label('Feestdagen')
                        ->options(ApplyOnHoliday::class)
                        ->default(ApplyOnHoliday::No->name)
                        ->required()
                        ->hint(
                            new HtmlString(view('filament.components.hint-icon', [
                                'tooltip' => '<b>Ja</b>: De taakplanner wordt ook uitgevoerd op feestdagen.</br></br><b>Nee</b>: De taakplanner wordt niet uitgevoerd op feestdagen.</br></br><b>Alleen op feestdagen</b>: De taakplanner wordt uitgevoerd op feestdagen, maar niet op andere dagen.',
                            ])->render())
                        ),

                    Select::make('action')
                        ->label('Actie')
                        ->options(TaskPlannerAction::class)
                        ->default(TaskPlannerAction::Add->name)
                        ->selectablePlaceholder(false)
                        ->required()
                        ->hint(
                            new HtmlString(view('filament.components.hint-icon', [
                                'tooltip' => '<b>Toevoegen</b>: Als een taak al bestaat en nog niet is afgehandeld, wordt er een extra bij gemaakt.<br><br><b>Vervangen</b>: Als een taak al bestaat en nog niet is afgehandeld, wordt deze vervangen door de nieuwe taak.',
                            ])->render())
                        ),

                ])->columnSpan([
                    'default' => 12,
                    'md' => 4,
                ]),

                Group::make()
                    ->schema([
                        Section::make('Toewijzing')
                            ->schema([
                                select::make('assignments.users')
                                    ->label('Medewerkers')
                                    ->getSearchResultsUsing(
                                        fn(string $search, Get $get): array =>
                                        User::byTeams()
                                            ->where(function ($query) use ($search) {
                                                $query->where('firstname', 'like', "{$search}%")
                                                    ->orWhere('lastname', 'like', "{$search}%");
                                            })
                                            ->limit(50)->get()->pluck('full_name', 'id')->toArray()
                                    )
                                    ->getOptionLabelsUsing(fn(array $values): array => User::whereIn('id', $values)->get()->pluck('full_name', 'id')->toArray())
                                    ->multiple()
                                    ->placeholder('Wijs persoon toe')
                                    ->hint(
                                        new HtmlString(view('filament.components.hint-icon', [
                                            'tooltip' => 'Enkel teamleden kunnen aan taakplanner gekoppeld worden.</br></br>Als <b>"Eenmalig toewijzing"</b> is aangevinkt, worden de geselecteerde personen alleen aan de volgende taak toegewezen',
                                        ])->render())
                                    ),

                                Checkbox::make('assignments.one_time_recurrence')
                                    ->label(new HtmlString('<span class="text-sm opacity-70">Eenmalige toewijzing</span>'))
                                    ->default(false)
                                    ->extraAttributes(['class' => 'custom-checkbox-label']),

                                Select::make('executionTeams')
                                    ->label('Uitvoerende teams')
                                    ->relationship(name: 'executionTeams', titleAttribute: 'name')
                                    ->multiple()
                                    ->hidden(function (Set $set, Get $get, $livewire) {
                                        $arrOfTeamIds = self::computeSuggestedIds($get, $livewire);
                                        $set('executionTeams', $arrOfTeamIds);

                                        return false;
                                    })
                                    ->validationAttribute('Uitvoerende teams')
                                    ->requiredWithout('assignments.users')
                                    ->validationMessages([
                                        'required_without' => 'Een taaktoewijzingsregel is vereist wanneer er geen medewerkers zijn geselecteerd',
                                    ])
                                    ->multiple()
                                    ->extraAttributes(['class' => 'hidden'])
                                    ->hint(
                                        new HtmlString(view('filament.components.hint-icon', [
                                            'tooltip' => 'Toont de teams waaraan deze taakplanner een taak toewijst, op basis van de huidige toewijzingsregels<br><br>
            <span class="text-green-600">Groen</span> = toegevoegd<br><span class="text-red-600 mr-1">Rood</span> = verwijderd<br>
            <span class="text-gray-500 mr-2">Grijs</span> = ongewijzigd',
                                        ])->render())
                                    )
                                    ->view('filament.components.teams', function (Get $get, $livewire) {
                                        $suggested = $get('executionTeams') ?? [];

                                        $existing = $livewire?->record?->executionTeams()
                                            ->byTeamsUserBelongsTo()
                                            ->pluck('teams.id')
                                            ->toArray() ?? [];

                                        $toAdd = array_diff($suggested, $existing);
                                        $toRemove = array_diff($existing, $suggested);
                                        $unchanged = array_intersect($existing, $suggested);

                                        $allIds = array_unique(array_merge($toAdd, $toRemove, $unchanged));
                                        $names = Team::whereIn('id', $allIds)->pluck('name', 'id')->all();

                                        return [
                                            'suggested' => $suggested,
                                            'existing' => $existing,
                                            'toAdd' => $toAdd,
                                            'toRemove' => $toRemove,
                                            'unchanged' => $unchanged,
                                            'names' => $names,
                                        ];
                                    }),

                            ]),

                        Group::make()
                            ->schema([
                                Section::make('')
                                    ->schema([

                                        Select::make('ownerTeams')
                                            ->label('Eigenaarsteam')
                                            ->relationship(
                                                name: 'ownerTeams',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: function (Builder $query): Builder {
                                                    $user = Auth::user();

                                                    return $query
                                                        ->when(! $user?->isSuperAdmin(), function (Builder $query) use ($user) {
                                                            $query->whereIn(
                                                                'teams.id',
                                                                $user?->teams()->pluck('teams.id')->toArray() ?? []
                                                            );
                                                        })
                                                        ->orderBy('name');
                                                }
                                            )
                                            ->multiple()
                                            ->pivotData([
                                                'role' => TeamRole::Owner->value,
                                            ])
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get, ?array $state) {
                                                $taskTypeId = $get('task_type_id');
                                                $user = Auth::user();

                                                $ownerTeamId = filled($state) ? (int) $state[0] : null;

                                                if (! $taskTypeId || ! $ownerTeamId) {
                                                    $set('task_type_id', null);
                                                    return;
                                                }

                                                $isStillAllowed = TaskType::query()
                                                    ->whereKey($taskTypeId)
                                                    ->byAvailableToTeams([$ownerTeamId])
                                                    ->exists() || $user?->isSuperAdmin();

                                                if (! $isStillAllowed) {
                                                    $set('task_type_id', null);
                                                    $set('name', null);
                                                }
                                            }),

                                        Toggle::make('is_active')
                                            ->label('Actief')
                                            ->default(true),
                                    ]),

                            ])

                    ])->columnSpan([
                        'default' => 12,
                        'md' => 2,
                    ]),


            ])->columnSpan(6)->columns(6),

            HasFilamentTeamFields::creatorField(),

        ])->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query): Builder => $query->withGlobalScope('active', function (Builder $builder) {
                $builder->where('is_active', true);
            }))
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('executionTeams.id')
                    ->label('Toewijzingen')
                    ->getStateUsing(function ($record) {
                        $teamNames = $record->executionTeams->pluck('name')->toArray();

                        $assignments = is_string($record->assignments)
                            ? json_decode($record->assignments, true)
                            : $record->assignments;

                        $userNames = [];

                        if (! empty($assignments['users'])) {
                            $userNames = User::whereIn('id', $assignments['users'])
                                ->get()
                                ->map(fn($user) => "{$user->firstname} {$user->lastname}")
                                ->toArray();
                        }

                        return collect($teamNames)->merge($userNames);
                    })
                    ->listWithLineBreaks(),

                ViewColumn::make('frequency')
                    ->label('Frequentie')
                    ->view('filament.tables.columns.interval')
                    ->state(fn($record) => [
                        'frequency' => $record->frequency,
                        'interval' => $record->interval,
                    ]),

                TextColumn::make('campus.name')
                    ->label('Locatie')
                    ->sortable()
                    ->getStateUsing(
                        fn($record) => "<div>{$record->campus?->name}</div> <div class='text-xs text-gray-500 dark:text-gray-400'>{$record->space?->name}</div>"
                    )
                    ->html(),

                TextColumn::make('taskType.name')
                    ->label('Taaktype')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $subHtmlData = '';

                        if ($record->visit) {
                            $subHtmlData = "<div class='text-xs text-gray-500 dark:text-gray-400'>{$record->visit->patient?->firstname} {$record->visit->patient?->lastname} ({$record->visit->patient?->gender}) - {$record->visit->bed?->room?->number} {$record->visit->bed?->number}<br>{$record->visit->number}</div>";
                        }

                        return "<div>{$record->taskType?->name}</div> {$subHtmlData}";
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query
                            ->orWhereHas('taskType', fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('visit', fn($q) => $q->byPatientName($search));
                    })
                    ->html(),

                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge(),

                TextColumn::make('on_holiday')
                    ->label('Feestdagen')
                    ->sortable(),

                TextColumn::make('next_run_at')
                    ->label('Ingepland voor')
                    ->dateTime('j F Y H:i')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Actief')
                    ->sortable()
                    ->icon(fn(bool $state): string => match ($state) {
                        false => 'heroicon-o-x-circle',
                        true => 'heroicon-o-check-circle',
                    })
                    ->color(fn(bool $state): string => match ($state) {
                        false => 'gray',
                        true => 'success',
                    }),
            ])

            ->filters([

                SelectFilter::make('executionTeams')
                    ->label('Uitvoerend team')
                    ->relationship(
                        'executionTeams',
                        'name',
                        fn($query) => $query->whereIn('teams.id', Auth::user()->teams->pluck('id'))
                    ),

                Filter::make('next_run_at')
                    ->label('Ingepland voor')
                    ->form([
                        DatePicker::make('date')
                            ->label('Ingepland voor')
                            ->native(false)
                            ->displayFormat('j F Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date'] ?? null,
                            fn(Builder $query, $date): Builder => $query->whereDate('next_run_at', $date),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['date'] ?? null)) {
                            return null;
                        }

                        return 'Ingepland voor: ' . Carbon::parse($data['date'])->translatedFormat('j F Y');
                    }),

                Filter::make('inactives')
                    ->label('Inactieven')
                    ->toggle()
                    ->baseQuery(function (Builder $query) {
                        return $query->withoutGlobalScope('active')->where('is_active', false);
                    }),
            ])

            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Bewerken'),

                    Action::make('ExecuteNow')
                        ->label('Taakplanner nu uitvoeren')
                        ->modalDescription('Deze actie voert de taak nu uit in plaats van op de geplande datum. De volgende uitvoeringsdatum wordt automatisch aangepast')
                        ->modalAlignment('center')
                        ->modalWidth('md')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalSubmitActionLabel('Bevestigen')
                        ->icon('heroicon-o-cog')
                        ->action(function (TaskPlanner $record, TaskPlannerService $taskPlannerService) {
                            $taskPlannerService->reschedule($record);
                            $taskPlannerService->execute($record, TaskStatusEnum::Added, Carbon::now());

                            Notification::make()
                                ->title('De taakplanner is succesvol uitgevoerd.')
                                ->success()
                                ->send();
                        })
                        ->modalFooterActionsAlignment(Alignment::Center)
                        ->modalAutofocus(false),

                    Action::make('createOneTimeTask')
                        ->label('Genereer eenmalige taak')
                        ->modalDescription('Deze actie maakt een ingeplande taak aan op basis van de gekozen taakplanner. De volgende geplande datum van de taakplanner wordt niet automatisch aangepast')
                        ->modalAlignment('center')
                        ->modalWidth('md')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalSubmitActionLabel('Bevestigen')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Section::make([
                                DateTimePicker::make('next_run_at')
                                    ->label('Ingepland voor')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->displayFormat('j F Y H:i')
                                    ->rule('after:now')
                                    ->validationMessages([
                                        'after' => 'Ingepland voor moet een datum in de toekomst zijn.',
                                    ])
                                    ->default(fn(?Model $record) => $record?->next_run_at),

                                Select::make('action')
                                    ->label('Actie')
                                    ->options(TaskPlannerAction::class)
                                    ->default(fn(Taskplanner $record) => $record->action->name)
                                    ->selectablePlaceholder(false)
                                    ->required()
                                    ->hint(
                                        new HtmlString(view('filament.components.hint-icon', [
                                            'tooltip' => '<b>Toevoegen</b>: Als een taak al bestaat en nog niet is afgehandeld, wordt er een extra bij gemaakt.<br><br><b>Vervangen</b>: Als een taak al bestaat en nog niet is afgehandeld, wordt deze vervangen door de nieuwe taak.',
                                        ])->render())
                                    ),

                                select::make('assignments.users')
                                    ->label('Toewijzing')
                                    ->getSearchResultsUsing(fn(string $search): array => User::byTeams()->where('firstname', 'like', "{$search}%")->orWhere('lastname', 'like', "{$search}%")->limit(50)->get()->pluck('full_name', 'id')->toArray())
                                    ->getOptionLabelsUsing(fn(array $values): array => User::whereIn('id', $values)->get()->pluck('full_name', 'id')->toArray())
                                    ->default(fn(Taskplanner $record) => $record->assignments['users'])
                                    ->multiple()
                                    ->placeholder('Wijs persoon toe'),

                            ])->columns(1),
                        ])
                        ->action(function (TaskPlanner $record, array $data, TaskPlannerService $taskPlannerService) {
                            $record->next_run_at = $data['next_run_at'];
                            $record->action = $data['action'];
                            $record->assignments = $data['assignments'];
                            $taskPlannerService->execute($record, TaskStatusEnum::fromStartDateTime($record->next_run_at));
                            Notification::make()
                                ->title('Taak succesvol aangemaakt')
                                ->success()
                                ->send();
                        })
                        ->modalFooterActionsAlignment(Alignment::Center)
                        ->modalAutofocus(false),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Dupliceren')
                        ->icon('heroicon-o-document-duplicate')
                        ->url(fn($record) => TaskPlannerResource::getUrl('create', [
                            'duplicate_from' => $record->getKey(),
                        ])),
                    DeleteAction::make(),
                ]),

            ])
            ->view('filament.components.task-planner-table')
            ->poll('30s')
            ->bulkActions([

                DeleteBulkAction::make()
                    ->label('Verwijderen')
                    ->modalHeading('Records verwijderen')
                    ->modalDescription('Weet je zeker dat je de geselecteerde records wilt verwijderen?')
                    ->modalSubmitActionLabel('Verwijderen')
                    ->visible(
                        fn($livewire) => data_get($livewire->getTableFilterState('inactives'), 'isActive') === false
                    )
                    ->deselectRecordsAfterCompletion(),

                // Only show when filtering to active rows
                BulkAction::make('Inactiveren')
                    ->visible(
                        fn($livewire) => (data_get($livewire->getTableFilterState('inactives'), 'isActive') === false)
                    )
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $ids = $records->modelKeys();
                        if (!$ids) return;

                        DB::transaction(function () use ($ids) {
                            $model = static::getModel();
                            foreach (array_chunk($ids, 500) as $chunk) {
                                $model::whereKey($chunk)
                                    ->where('is_active', true)   // idempotent
                                    ->update(['is_active' => false]);
                            }
                        });
                    })
                    ->deselectRecordsAfterCompletion(),

                // Only show when filtering to inactive rows
                BulkAction::make('Activeren')
                    ->visible(
                        fn($livewire) => (data_get($livewire->getTableFilterState('inactives'), 'isActive') === true)
                    )
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $ids = $records->modelKeys();
                        if (!$ids) return;

                        // Let the service do per-record transactions; chunk to keep memory low.
                        TaskPlanner::query()
                            ->select(['id', 'is_active', 'next_run_at', 'frequency', 'interval']) // keep it lean; add columns your service needs
                            ->whereIn('id', $ids)
                            ->where('is_active', false)
                            ->orderBy('id')
                            ->chunkById(200, function ($chunk) {
                                foreach ($chunk as $tp) {
                                    // The service should be idempotent and set next_run_at > now()
                                    $tp->activate();
                                }
                            }, 'id');
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('name', 'asc')
                    ->orderBy('name', 'asc');
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'ownerTeams',
                'executionTeams',
                'taskType',
                'visit.bed.room',
                'visit.patient',
                'space',
                'tasks' => fn($query) => $query->byActive()->orWhere(fn($query) => $query->byScheduled())->orderBy('start_date_time')->with('status'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaskPlanners::route('/'),
            'create' => Pages\CreateTaskPlanner::route('/create'),
            'edit' => Pages\EditTaskPlanner::route('/{record}/edit'),
        ];
    }

    // Override the navigation label to use the singular model label
    public static function getNavigationLabel(): string
    {
        return static::getModelLabel();  // Use singular label here
    }

    /**
     * Build a “fake” TaskPlanner from the current form state,
     * run the assignment‐rules, and return an array of IDs.
     */
    protected static function computeSuggestedIds(Get $get, $livewire): array
    {
        $planner = new TaskPlanner(Arr::except($get(), ['frequency']));
        $task = $planner
            ->toTaskModel()
            ->setRelation(
                'tags',
                collect($get('tags') ?? [])
                    ->map(fn($id) => ['id' => (int) $id])
            );

        return TaskAssignmentService::getExecutionTeamsByTaskMatch($task)
            ->byTeamsUserBelongsTo()
            ->pluck('id')
            ->toArray();
    }
}
