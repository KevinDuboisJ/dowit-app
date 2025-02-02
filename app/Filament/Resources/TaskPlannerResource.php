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
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Services\TaskAssignmentService;
use Illuminate\Support\HtmlString;

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
        $now = Carbon::now()->format('Y-m-d H:i:s');

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
                        // dd($record->start_date_time);
                        // Apply 'after' validation only in the 'create' context or in the 'edit' context when the value has been changed by the user
                        $now = now();

                        if ($context === 'edit') {
                            // Validate only if the value has changed
                            return $record->start_date_time !== $state ? $now : null;
                        }

                        return $now;
                    }),

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
                    ->getOptionLabelFromRecordUsing(fn(Space $record) => "{$record->name} ({$record->_spccode})")
                    ->searchable(['name', '_spccode'])
                    ->preload()
                    ->required()
                    ->live(),

                Select::make('space_to_id')
                    ->label('Bestemmingslocatie')
                    ->native(false)
                    ->relationship('spaceTo', 'name')
                    ->getOptionLabelFromRecordUsing(fn(Space $record) => "{$record->name} ({$record->_spccode})")
                    ->searchable(['name', '_spccode'])
                    ->required()
                    ->visible(
                        fn(Get $get): bool => $get('task_type_id') === '3'
                    ),

                Section::make([
                    Select::make('frequency')
                        ->label('Frequentie')
                        ->options(TaskPlannerFrequency::class)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(Select $component) => $component
                            ->getContainer()
                            ->getComponent('dynamicFields')
                            ->getChildComponentContainer()
                            ->fill()),

                    Grid::make()
                        ->schema(fn(Get $get): array => match ($get('frequency')) {
                            TaskPlannerFrequency::SpecificDays->name => [
                                Select::make('interval')
                                    ->label('Dagen')
                                    ->options(DaysOfWeek::class)
                                    ->multiple()
                                    ->required()
                            ],

                            TaskPlannerFrequency::Weekly->name => [
                                Select::make('interval')
                                    ->label('Dag')
                                    ->options(DaysOfWeek::class)
                                    ->required(),
                            ],

                            TaskPlannerFrequency::EachXDay->name => [
                                TextInput::make('interval')
                                    ->label('Interval')
                                    ->numeric()
                                    ->required(),
                            ],
                            default => [],
                        })
                        ->key('dynamicFields'),
                ]),

                // TextInput doesn't automatically convert an array to a string, unlike the TextColumn. To Solve this i use formatStateUsing
                Select::make('on_holiday')
                    ->label('Feestdagen')
                    ->options(ApplyOnHoliday::class)
                    ->default(ApplyOnHoliday::No->name)
                    ->required(),

                Select::make('action')
                    ->label('Actie')
                    ->options(TaskPlannerAction::class)
                    ->default(TaskPlannerAction::Add->name)
                    ->selectablePlaceholder(false)
                    ->required(),

                Textarea::make('comment')
                    ->label('commentaar')
                    ->nullable()
                    ->rows(1)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Actief')
                    ->required(),

            ])->columnSpan(6)->columns(2),
            Section::make([

                select::make('assignments.users')
                    ->label('Toewijzing')
                    ->getSearchResultsUsing(fn(string $search): array => User::where('firstname', 'like', "{$search}%")->excludeSystemUser()->orWhere('lastname', 'like', "{$search}%")->limit(50)->get()->pluck('full_name', 'id')->toArray())
                    ->getOptionLabelsUsing(fn(array $values): array => User::whereIn('id', $values)->get()->pluck('full_name', 'id')->toArray())
                    ->multiple()
                    ->placeholder('Wijs persoon toe')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enkel personen uit teams waartoe de gebruiker die de taakplanner invult behoort, kunnen toegewezen worden.'),

                Checkbox::make('assignments.one_time_recurrence')
                    ->label(new HtmlString('<span class="text-sm opacity-70">Eenmalige toewijzing</span>'))
                    ->helperText(new HtmlString('<div class="pb-4 border-b"><span class="text-xs/5 text-gray-500">Als dit is aangevinkt, worden de geselecteerde personen alleen aan de volgende taak toegewezen</span></div>'))
                    ->default(false)
                    ->extraAttributes(['class' => 'custom-checkbox-label']),

                Placeholder::make('Teams')
                    ->content(function (Get $get): HtmlString {

                        $string = '<ul role="list" class="space-y-2 divide-y divide-gray-100">';
                        $teams = TaskAssignmentService::getTeamsMatchingAssignmentRules(new Task([
                            'campus_id' => $get('campus_id') ?? null,
                            'task_type_id' => $get('task_type_id') ?? null,
                            'space_id' => $get('space_id') ?? null,
                            'space_to_id' => $get('space_to_id') ?? null,
                        ]));

                        foreach ($teams as $team) {
                            $string .= '
                                <li class="flex justify-between gap-x-6">
                                    <div class="flex min-w-0 gap-x-4">
                                        <p class="text-sm/5 text-gray-500">• ' . $team->name . '</p>
                                    </div>
                                </li>';
                        }
                        $string .= '</ul>';
                        return new HtmlString($string);
                    })
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Dit toont de teams waaraan deze taakplanner de taak zal toewijzen op basis van de huidige taaktoewijzingsregels'),

            ])->columnSpan(2),

        ])->columns(8);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('start_date_time')->label('Startdatum/tijd'),
                TextColumn::make('name')->label('Naam'),
                TextColumn::make('frequency')->label('Frequentie'),
                ViewColumn::make('interval')
                    ->view('filament.tables.columns.jsonEnum')
                    ->label('Interval'),
                TextColumn::make('campus.name')->label('Campus'),
                TextColumn::make('space.name')->label('Locatie'),
                TextColumn::make('taskType.name')->label('Taaktype'),
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
                        ->icon('heroicon-o-cog') // Using Font Awesome
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
                        ->requiresConfirmation()
                ]),


                // ->modalHeading('Delete post')
                // ->modalDescription('Are you sure you\'d like to delete this post? This cannot be undone.')
                // ->modalSubmitActionLabel('Yes, delete it'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('next_run_at', 'asc')
            ->defaultSort('is_active', 'desc');

    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
}
