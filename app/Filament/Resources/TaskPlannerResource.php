<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskPlannerResource\Pages;
use App\Models\TaskPlanner;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Enums\TaskPlannerFrequency;
use App\Enums\ApplyOnHoliday;
use App\Enums\TaskPlannerAction;
use App\Enums\DaysOfWeek;
use App\Enums\TaskStatus as TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Filament\Components\PatientAutocomplete;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Support\Carbon;
use App\Services\TaskPlannerService;
use Illuminate\Support\Facades\Auth;
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
use App\Models\PATIENTLIST\Patient;
use App\Models\PATIENTLIST\Visit;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Services\TaskAssignmentService;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use App\Traits\HasFilamentTeamFields;
use App\Models\Team;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Arr;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Filament\Forms\Components\Hidden;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Livewire\Component as Livewire;

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
                    ->relationship('taskType', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
                        if (empty($get('name'))) {
                            $set('name', TaskTypeEnum::from($state)->getLabel());
                        }
                    }),

                Select::make('campus_id')
                    ->label('Campus')
                    ->relationship('campus', 'name')
                    ->required()
                    ->live(),

                TextInput::make('name')
                    ->label('Naam')
                    ->nullable()
                    ->required(),

                Flatpickr::make('next_run_at')
                    ->label('Uitvoerstijdstip')
                    ->required()
                    ->time(true)
                    ->seconds(false)
                    ->native(true)
                    ->displayFormat(' j F Y H:i')
                    ->after(function ($state, ?string $context, ?TaskPlanner $record) {
                        // Apply 'after' validation only in the 'create' context or in the 'edit' context when the value has been changed by the user
                        $now = now();

                        if ($context === 'edit') {
                            // Validate only if the value has changed
                            return $record->next_run_at !== $state ? $now : null;
                        }

                        return $now;
                    })->hint(
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
                        fn(Get $get): bool => in_array((int) $get('task_type_id'), TaskTypeEnum::getPatientTransportIds())
                    )
                    ->afterStateHydrated(function ($component, $state) {
                        if ($state) {
                            $patient = Visit::with(['patient', 'bed.room'])->byIsAdmitted()->find($state);
                            $component->state($patient);
                        }
                    })
                    ->dehydrateStateUsing(fn($state) => $state['id']),

                Select::make('space_id')
                    ->label('locatie')
                    ->native(false)
                    ->relationship('space', 'name')
                    ->searchable(['name', '_spccode'])
                    ->getSearchResultsUsing(function (string $search) {
                        return Space::query()
                            ->byUserInput($search) // ← use your scope here
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($space) => [
                                $space->id => "{$space->name} ({$space->_spccode})",
                            ]);
                    })
                    ->live(),

                Select::make('space_to_id')
                    ->label('Bestemmingslocatie')
                    ->relationship('spaceTo', 'name')
                    ->searchable(['name', '_spccode'])
                    ->getSearchResultsUsing(function (string $search) {
                        return Space::query()
                            ->byUserInput($search) // ← use your scope here
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($space) => [
                                $space->id => "{$space->name} ({$space->_spccode})",
                            ]);
                    })
                    ->visible(
                        fn(Get $get): bool => in_array((int) $get('task_type_id'), TaskTypeEnum::getPatientTransportIds())
                    )
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
                                            'Sunday' => 'Zondag',
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

                                Select::make('teams')
                                    ->label('Teams')
                                    ->relationship(name: 'teams', titleAttribute: 'name',)
                                    // when the form is hydrated (create or edit), compute IDs
                                    ->hidden(function (Set $set, Get $get, $livewire) {
                                        $arrOfTeamIds = self::computeSuggestedIds($get, $livewire);
                                        $set('teams', $arrOfTeamIds);
                                        return false;
                                    })
                                    ->validationAttribute('Teams')
                                    ->requiredWithout('assignments.users')
                                    ->validationMessages(['required_without' => 'Een taaktoewijzingsregel is vereist wanneer er geen medewerkers zijn geselecteerd'])
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
                                        // Suggested IDs based on current form state
                                        $suggested = $get('teams') ?? [];

                                        // Existing team IDs on the record
                                        $existing  = $livewire?->record?->teams()->byTeamsUserBelongsTo()->pluck('teams.id')->toArray() ?? [];

                                        // Calculate diff
                                        $toAdd     = array_diff($suggested, $existing);
                                        $toRemove  = array_diff($existing,  $suggested);
                                        $unchanged = array_intersect($existing, $suggested);

                                        // Batch fetch all names in one go
                                        $allIds = array_unique(array_merge($toAdd, $toRemove, $unchanged));
                                        $names  = Team::whereIn('id', $allIds)->pluck('name', 'id')->all();

                                        return [
                                            'suggested' => $suggested,
                                            'existing'  => $existing,
                                            'toAdd'     => $toAdd,
                                            'toRemove'  => $toRemove,
                                            'unchanged' => $unchanged,
                                            'names'     => $names,
                                        ];
                                    }),


                                // \Filament\Forms\Components\Actions::make([
                                //     \Filament\Forms\Components\Actions\Action::make('createRule')
                                //         ->label('Team toevoegen')
                                //         ->icon('heroicon-m-plus')
                                //         ->modalHeading('Voeg een regel toe')
                                //         ->modalSubmitActionLabel('Opslaan')
                                //         ->form(function (Form $form) {
                                //             $form->fill();
                                //             return TaskAssignmentRuleResource::form($form);
                                //         })
                                // ]),

                            ]),

                        Group::make()
                            ->schema([
                                Section::make('')
                                    ->schema([
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
                TextColumn::make('name')->label('Naam'),
                TextColumn::make('teams.id')
                    ->label('Teams/Medewerkers')
                    ->getStateUsing(function ($record) {
                        // 1) Get team names
                        $teamNames = $record->teams->pluck('name')->toArray();

                        // 2) Decode the assignments JSON
                        $assignments = is_string($record->assignments)
                            ? json_decode($record->assignments, true)
                            : $record->assignments;

                        // 3) Fetch user names if there are assigned user IDs
                        $userNames = [];
                        if (!empty($assignments['users'])) {
                            $userNames = User::whereIn('id', $assignments['users'])
                                ->get()
                                ->map(fn($user) => "{$user->firstname} {$user->lastname}")
                                ->toArray();
                        }

                        // 4) Merge teams + employees
                        return collect($teamNames)
                            ->merge($userNames);
                    })
                    ->listWithLineBreaks(),

                ViewColumn::make('frequency')
                    ->label('Frequentie')
                    ->view('filament.tables.columns.interval')
                    ->state(fn($record) => [
                        'frequency' => $record->frequency,
                        'interval'  => $record->interval,
                    ]),

                TextColumn::make('campus.name')
                    ->label('Locatie')
                    ->getStateUsing(
                        fn($record) => "<div>{$record->campus?->name}</div> <div class='text-xs text-gray-500 dark:text-gray-400'>{$record->space?->name}</div>"
                    )
                    ->html(),

                TextColumn::make('taskType.name')
                    ->label('Taaktype')
                    ->getStateUsing(
                        function ($record) {
                            $subHtmlData = "";
                            if ($record->visit) {
                                $subHtmlData = "<div class='text-xs text-gray-500 dark:text-gray-400'>{$record->visit->patient?->firstname} {$record->visit->patient?->lastname} ({$record->visit->patient?->gender}) - {$record->visit->bed?->room?->number} {$record->visit->bed?->number}<br>{$record->visit->number}</div>";
                            }
                            return "<div>{$record->taskType?->name}</div> $subHtmlData";
                        }
                    )
                    ->html(),

                TextColumn::make('tags.name')->label('Tags')->badge(),
                TextColumn::make('on_holiday')->label('Feestdagen'),

                TextColumn::make('next_run_at')
                    ->label('Ingepland voor')
                    ->dateTime('j F Y H:i'),

                IconColumn::make('is_active')
                    ->label('Actief')
                    ->icon(fn(string $state): string => match ($state) {
                        '0' => 'heroicon-o-x-circle',
                        '1' => 'heroicon-o-check-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        '0' => 'gray',
                        '1' => 'success',
                    }),
            ])

            ->filters([
                // Filter to show only inactive records
                Filter::make('inactief')
                    ->label('Toon inactieve')
                    ->baseQuery(function (Builder $query) {
                        return $query->withoutGlobalScope('active')->where('is_active', false);
                    }),
            ], layout: FiltersLayout::AboveContent)

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
                            $taskPlannerService->createTask($record, TaskStatusEnum::Added, Carbon::now());
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
                                Flatpickr::make('next_run_at')
                                    ->label('Ingepland voor')
                                    ->required()
                                    ->time(true)
                                    ->seconds(false)
                                    ->displayFormat(' j F Y H:i')
                                    ->after(now())
                                    ->default(function (Model $record) {
                                        return $record->next_run_at;
                                    }),

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
                            $taskPlannerService->createTask($record, TaskStatusEnum::fromStartDateTime($data['next_run_at']));
                        })
                        ->modalFooterActionsAlignment(Alignment::Center)
                        ->modalAutofocus(false),


                    DeleteAction::make(),
                ]),

            ])
            ->view('filament.components.task-planner-table')
            ->poll('30s')
            ->bulkActions([])
            ->defaultSort('next_run_at', 'asc')
            ->defaultSort('is_active', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
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

        return TaskAssignmentService::getTeamsFromTheAssignmentRulesByTaskMatchAndTeams($task)
            ->byTeamsUserBelongsTo()
            ->pluck('id')
            ->toArray();
    }
}
