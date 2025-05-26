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
use App\Enums\TaskStatus;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Support\Carbon;
use App\Services\TaskPlannerService;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Set;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use App\Models\User;
use App\Models\Asset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Services\TaskAssignmentService;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use App\Traits\HasFilamentTeamFields;
use Filament\Tables\Actions\DeleteAction;
use App\Models\Team;
use Illuminate\Support\Arr;

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
                TextInput::make('name')
                    ->label('Naam')
                    ->nullable()
                    ->required(),

                DateTimePicker::make('start_date_time')
                    ->label('Startdatum')
                    ->required()
                    ->seconds(false)
                    ->after(function ($state, ?string $context, ?TaskPlanner $record) {
                        // Apply 'after' validation only in the 'create' context or in the 'edit' context when the value has been changed by the user
                        $now = now();

                        if ($context === 'edit') {
                            // Validate only if the value has changed
                            return $record->start_date_time !== $state ? $now : null;
                        }

                        return $now;
                    })
                    ->native(false),

                Textarea::make('description')
                    ->label('Omschrijving')
                    ->nullable()
                    ->rows(1)
                    ->columnSpanFull(),

                Select::make('task_type_id')
                    ->label('Taaktype')
                    ->relationship('taskType', 'name')
                    ->required()
                    ->live(),

                Select::make('campus_id')
                    ->label('Campus')
                    ->relationship('campus', 'name')
                    ->required()
                    ->live(),

                Select::make('space_id')
                    ->label('locatie')
                    ->native(false)
                    ->relationship('space', 'name')
                    ->searchable(['name', '_spccode'])
                    ->getSearchResultsUsing(function (string $search) {
                        return Space::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('_spccode', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($space) => [
                                $space->id => "{$space->name} ({$space->_spccode})",
                            ]);
                    })
                    ->required()
                    ->live(),

                Select::make('space_to_id')
                    ->label('Bestemmingslocatie')
                    ->native(false)
                    ->relationship('spaceTo', 'name')
                    ->searchable(['name', '_spccode'])
                    ->getSearchResultsUsing(function (string $search) {
                        return Space::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('_spccode', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($space) => [
                                $space->id => "{$space->name} ({$space->_spccode})",
                            ]);
                    })
                    ->required()
                    ->visible(
                        fn(Get $get): bool => $get('task_type_id') === '1'
                    ),


                RichEditor::make('comment')
                    ->label('commentaar')
                    ->toolbarButtons([
                        'bold',
                        'bulletList',
                        'h2',
                        'italic',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->columnSpan(6)
                    ->placeholder('Typ hier uw commentaar...')
                    ->nullable()
                    ->columnSpanFull(),

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
                                $component
                                    ->getContainer()
                                    ->getComponent('dynamicFields')
                                    ->getChildComponentContainer()
                                    ->fill();
                            }),

                        Group::make()
                            ->schema(fn(Get $get): array => match ($get('frequency')) {
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

                                default => [],
                            })
                            ->key('dynamicFields'),
                    ])->columns(2),

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
                                    ->getSearchResultsUsing(fn(string $search): array => User::where(function ($query) use ($search) {
                                        $query->where('firstname', 'like', "{$search}%")
                                            ->orWhere('lastname', 'like', "{$search}%");
                                    })
                                        ->excludeSystemUser()
                                        ->byTeams()
                                        ->limit(50)->get()->pluck('full_name', 'id')->toArray())
                                    ->getOptionLabelsUsing(fn(array $values): array => User::whereIn('id', $values)->get()->pluck('full_name', 'id')->toArray())
                                    ->multiple()
                                    ->placeholder('Wijs persoon toe')
                                    ->hint(
                                        new HtmlString(view('filament.components.hint-icon', [
                                            'tooltip' => 'Dit toont de teams waaraan deze taakplanner de taak zal toewijzen op basis van de huidige taaktoewijzingsregels.</br></br>Als <b>"Eenmalig toewijzing"</b> is aangevinkt, worden de geselecteerde personen alleen aan de volgende taak toegewezen',
                                        ])->render())
                                    ),

                                Checkbox::make('assignments.one_time_recurrence')
                                    ->label(new HtmlString('<span class="text-sm opacity-70">Eenmalige toewijzing</span>'))
                                    ->default(false)
                                    ->extraAttributes(['class' => 'custom-checkbox-label']),

                                Select::make('teams')
                                    ->label('')
                                    ->relationship(name: 'teams', titleAttribute: 'name',)
                                    // when the form is hydrated (create or edit), compute IDs
                                    ->hidden(function (Get $get, Set $set, $livewire) {
                                        $set('teams', self::computeSuggestedIds($get, $livewire));
                                        return false;
                                    })
                                    ->multiple()
                                    ->extraAttributes(['class' => 'hidden']),

                                Placeholder::make('team_diff')
                                    ->label('Teams')
                                    ->content(function (Get $get, $livewire = null): HtmlString {

                                        // 1) Compute suggested & existing team IDs
                                        $suggested   = $get('teams') ?? [];
                                        $existing    = $livewire?->record?->teams()->byTeamsUserBelongsTo()->pluck('teams.id')->toArray() ?? [];

                                        $toAdd     = array_diff($suggested, $existing);
                                        $toRemove  = array_diff($existing,  $suggested);
                                        $unchanged = array_intersect($existing, $suggested);

                                        // 2) Batch‐fetch all names in one go
                                        $allIds = array_unique(array_merge($toAdd, $toRemove, $unchanged));
                                        $names  = Team::whereIn('id', $allIds)
                                            ->pluck('name', 'id')
                                            ->all();

                                        // 3) Build lines with your custom span markup
                                        $lines = [];
                                        foreach ($toAdd as $id) {
                                            $lines[] = sprintf(
                                                '<li>• <span class="text-sm/5 text-green-600">%s +</span></li>',
                                                e($names[$id] ?? '–')
                                            );
                                        }
                                        foreach ($toRemove as $id) {
                                            $lines[] = sprintf(
                                                '<li>• <span class="text-sm/5 text-red-600">%s −</span></li>',
                                                e($names[$id] ?? '–')
                                            );
                                        }
                                        foreach ($unchanged as $id) {
                                            $lines[] = sprintf(
                                                '<li>• <span class="text-sm/5 text-gray-700">%s</span></li>',
                                                e($names[$id] ?? '–')
                                            );
                                        }

                                        return new HtmlString('<ul class="space-y-1">' . implode('', $lines) . '</ul>');
                                    })
                                    ->hint(
                                        new HtmlString(view('filament.components.hint-icon', [
                                            'tooltip' => 'Toont de teams waaraan deze taakplanner een taak toewijst, op basis van de huidige toewijzingsregels<br><br>
                                            <span class="text-green-600">Groen</span> = toegevoegd<br><span class="text-red-600 mr-1">Rood</span> = verwijderd<br>
                                            <span class="text-gray-500 mr-2">Grijs</span> = ongewijzigd',
                                        ])->render())
                                    ),
                            ]),

                        Group::make()
                            ->schema([
                                Section::make('')
                                    ->schema([
                                        Toggle::make('is_active')
                                            ->label('Actief'),
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
            ->query(fn() => TaskPlanner::query()->with('teams'))
            ->columns([

                TextColumn::make('name')->label('Naam'),

                TextColumn::make('teams')
                    ->label('Teams')
                    ->getStateUsing(fn($record) => $record->teams->pluck('name')->implode(', ')),

                TextColumn::make('frequency')->label('Frequentie'),

                ViewColumn::make('interval')
                    ->view('filament.tables.columns.jsonEnum')
                    ->label('Interval'),

                TextColumn::make('campus.name')->label('Campus'),

                TextColumn::make('space.name')->label('Locatie'),

                TextColumn::make('taskType.name')->label('Taaktype'),

                TextColumn::make('tags.name')->label('Tags')->badge(),

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

                TextColumn::make('on_holiday')
                    ->label('Feestdagen'),

                TextColumn::make('next_run_at')->label('Ingepland voor'),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Bewerken'),
                    Action::make('Activeren')
                        ->label('Activeren')
                        ->icon('heroicon-o-cog')
                        ->form([
                            Section::make([
                                select::make('assignments.users')
                                    ->label('Toewijzing')
                                    ->getSearchResultsUsing(fn(string $search): array => User::where('firstname', 'like', "{$search}%")->orWhere('lastname', 'like', "{$search}%")->limit(50)->get()->pluck('full_name', 'id')->toArray())
                                    ->getOptionLabelsUsing(fn(array $values): array => User::whereIn('id', $values)->get()->pluck('full_name', 'id')->toArray())
                                    ->multiple()
                                    ->placeholder('Wijs persoon toe'),

                            ])->columns(1),
                        ])
                        ->action(function (TaskPlanner $record, TaskPlannerService $taskPlannerService, array $data) {
                            $record->action = TaskPlannerAction::Add->name;
                            $record->assignments = $data['assignments'];
                            $taskPlannerService->triggerTask($record, TaskStatus::Added, Carbon::now());
                        })
                        ->modalAutofocus(false)
                        ->requiresConfirmation(),
                    DeleteAction::make(),
                ]),

            ])
            ->bulkActions([])
            ->defaultSort('next_run_at', 'asc')
            ->defaultSort('is_active', 'desc');
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

        // dd(TaskAssignmentService::getTeamsFromTheAssignmentRulesByTaskMatchAndTeams($task, Auth::user()->teams->pluck('id')->toArray())
        // ->byTeamsUserBelongsTo()
        // ->pluck('id')
        // ->toArray());

        return TaskAssignmentService::getTeamsFromTheAssignmentRulesByTaskMatchAndTeams($task, Auth::user()->teams->pluck('id')->toArray())
            ->byTeamsUserBelongsTo()
            ->pluck('id')
            ->toArray();
    }
}
