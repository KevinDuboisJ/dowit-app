<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskConfigurator;
use App\Filament\Resources\TaskAssignmentRuleResource\Pages;
use App\Filament\Resources\TaskAssignmentRuleResource\RelationManagers;
use App\Models\TaskAssignmentRule;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Models\Space;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use App\Models\TaskType;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use App\Traits\HasFilamentTeamFields;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Get;

class TaskAssignmentRuleResource extends Resource
{
    protected static ?string $model = TaskAssignmentRule::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square-stack';
    protected static ?string $modelLabel = 'Taaktoewijzingsregels';
    protected static ?string $pluralModelLabel = 'Taaktoewijzingsregels';
    protected static ?string $navigationGroup = 'Taakconfigurator';
    protected static ?int $navigationSort = 3;
    protected static array $staticOptions;

    public static function GetTheform(Form $form): Form
    {
        return self::form($form);
    }

    public static function form(Form $form): Form
    {
        self::addStaticOptions(TaskType::query()->pluck('name', 'id')->toArray());
        return $form
            ->schema([
                Textarea::make('description')
                    ->label('Omschrijving')
                    ->placeholder('Vul hier een omschrijving in')
                    ->required()
                    ->rows(1)
                    ->columnSpanFull(),

                Section::make([
                    Select::make('teams')
                        ->label('Teams')
                        ->relationship(
                            name: 'teams',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn($query) => $query->byTeamsUserBelongsTo()
                        )
                        ->multiple()
                        ->required(function (?Model $record, $state) {

                            if (!$record && empty($state)) {
                                return true;
                            }

                            $user = auth()->user();
                            $userTeamIds = $user->teams->pluck('id')->toArray();
                            $recordTeamIds = $record?->teams->pluck('id')->toArray() ?? [];
                            $hasAtLeastOneTeamUserDoesNotHave = !empty(array_diff($recordTeamIds, $userTeamIds));

                            return !$hasAtLeastOneTeamUserDoesNotHave;
                        })
                        ->helperText(function (?Model $record, $state) {

                            if (! $record) {
                                return null;
                            }

                            // Get the IDs of the teams the user can select from (i.e., user’s teams)
                            $userTeamIds = auth()->user()->teams->pluck('id')->toArray();

                            // Get the teams linked to the record but not part of user teams
                            $otherTeams = $record->teams
                                ->filter(fn($team) => !in_array($team->id, $userTeamIds))
                                ->pluck('name');

                            return $otherTeams->isNotEmpty()
                                ? 'Toegewezen aan andere teams: ' . $otherTeams->join(', ')
                                : null;
                        })

                ])->description('Kies aan welke teams deze regels worden toegepast'),
                Section::make([

                    Select::make('campuses')
                        ->label('Campus')
                        ->relationship(name: 'campus', titleAttribute: 'name')
                        ->dehydrated()
                        ->dehydrateStateUsing(fn(?array $state, Select $component): ?array => $state ? self::processSelectedOptions($state, $component) : null)
                        ->multiple()
                        ->preload()
                        ->afterStateHydrated(function (Select $component, ?array $state) {
                            $state ? $component->state(array_column($state, 'id')) : null;
                        }),

                    Select::make('task_types')
                        ->label('Taaktype')
                        ->relationship(name: 'taskType', titleAttribute: 'name')
                        ->dehydrated()
                        ->dehydrateStateUsing(fn(?array $state, Select $component): ?array => $state ? self::processSelectedOptions($state, $component) : null)
                        ->multiple()
                        ->preload()
                        ->afterStateHydrated(function (Select $component, ?array $state) {
                            $state ? $component->state(array_column($state, 'id')) : null;
                        }),

                    Select::make('spaces')
                        ->label('Locatie')
                        ->relationship(name: 'space', titleAttribute: 'name')
                        ->dehydrated()
                        ->dehydrateStateUsing(fn(?array $state, Select $component): ?array => $state ? self::processSelectedOptions($state, $component) : null)
                        ->multiple()
                        ->getOptionLabelFromRecordUsing(fn(Space $record) => "{$record->name} ({$record->_spccode})")
                        ->searchable(['name', '_spccode'])
                        ->formatStateUsing(fn(?array $state): ?array => $state ? array_column($state, 'id') : null),

                    Select::make('spaces_to')
                        ->label('Bestemmingslocatie')
                        ->relationship(name: 'spaceTo', titleAttribute: 'name')
                        ->dehydrated()
                        ->dehydrateStateUsing(fn(?array $state, Select $component): ?array => $state ? self::processSelectedOptions($state, $component) : null)
                        ->multiple()
                        ->getOptionLabelFromRecordUsing(fn(Space $record) => "{$record->name} ({$record->_spccode})")
                        ->searchable(['name', '_spccode'])
                        ->formatStateUsing(fn(?array $state): ?array => $state ? array_column($state, 'id') : null),

                    Select::make('tags')
                        ->label('Tags')
                        ->relationship(name: 'tags', titleAttribute: 'name')
                        ->dehydrated()
                        ->dehydrateStateUsing(fn(?array $state, Select $component): ?array => $state ? self::processSelectedOptions($state, $component) : null)
                        ->multiple()
                        ->preload()
                        ->afterStateHydrated(function (Select $component, ?array $state) {
                            $state ? $component->state(array_column($state, 'id')) : null;
                        }),

                ])->columnSpan(2)->columns(2)->description('Regels'),

                HasFilamentTeamFields::creatorField(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('id'),
                TextColumn::make('teams.name')
                    ->label('Teams')
                    ->formatStateUsing(fn($state, $record) => $record->teams->pluck('name')->join(', '))
                    ->sortable(),
                ViewColumn::make('campuses')
                    ->view('filament.tables.columns.jsonArray')
                    ->label('Campussen'),
                ViewColumn::make('task_types')
                    ->view('filament.tables.columns.jsonArray')
                    ->label('Taaktypes'),
                ViewColumn::make('spaces')
                    ->view('filament.tables.columns.jsonArray')
                    ->label('Locaties'),
                ViewColumn::make('spaces_to')
                    ->view('filament.tables.columns.jsonArray')
                    ->label('Bestemmingslocaties'),
                TextColumn::make('tags')
                    ->label('Tags')
                    ->formatStateUsing(fn($state) => $state['name'] ?? '')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Bewerken'),

                    Tables\Actions\DeleteAction::make(),
                ]),

            ])
            ->bulkActions([]);
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
            'index' => Pages\ListTaskAssignmentRules::route('/'),
        ];
    }

    // Method to add a new value to the static array
    public static function addStaticOptions(array $value): void
    {
        self::$staticOptions = $value;
    }

    // Method to get the static options
    public static function getStaticOptions(): array
    {
        return self::$staticOptions;
    }

    protected static function processSelectedOptions(array $state, Select $component): array
    {
        $result = [];
        $labels = $component->getOptionLabels();
        $index = 0;

        foreach ($labels as $id => $name) {
            $result[$index]['id'] = $id;
            $result[$index]['name'] = $name;
            $index++;
        }

        return $result;
    }

    protected static function getSelectedOptions(array $state): array
    {
        $result = [];
        $options = self::getStaticOptions();


        foreach ($state as $index => $item) {

            $result[$item['id']] = $options[$item['id']];
        }

        return $result;
    }
}
