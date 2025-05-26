<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use App\Filament\Resources\TeamUserResource\Pages\CreateTeamUser;
use App\Filament\Resources\TeamUserResource\Pages\EditTeamUser;
use App\Filament\Resources\TeamUserResource\Pages\ListTeamUsers;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;


class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->label('Teamnaam'),
                Checkbox::make('show_rules')
                    ->formatStateUsing(fn(?Team $record) => (bool) $record?->autoassign_rules)
                    ->disabled(fn(?Team $record, $context) => (bool) $record?->autoassign_rules && $context == 'edit')
                    ->columnSpanFull()
                    ->label('Automatische toewijzing')
                    ->reactive()
                    ->dehydrated(false), // Ensure the form reacts to changes in this checkbox

                TableRepeater::make('autoassign_rules')
                    ->label('Regels')
                    ->headers([
                        Header::make('Afdeling')->width('150px'),
                        Header::make('Functie')->width('150px'),
                    ])
                    ->schema([
                        TextInput::make('department_id')
                            ->requiredWithoutAll(['profession_id']),
                        TextInput::make('profession_id')
                            ->requiredWithoutAll(['department_id']),
                    ])
                    ->columnSpan('full')
                    ->streamlined()
                    ->reorderable(false)
                    ->visible(fn(callable $get) => $get('show_rules')),

                // Repeater::make('group_rules')
                //     ->label('Regels') // Use the correct field name from your model
                //     ->schema([
                //         TextInput::make('department_id')
                //             ->label('Afdeling')
                //             ->required(),

                //         TextInput::make('profession_id')
                //             ->label('Functie')
                //             ->required(),

                //     ])
                //     ->addActionAlignment(Alignment::Start)


                // Select::make('parent_team_id')
                //     ->label('Bovenliggend team')
                //     ->relationship('parentTeam', 'name',  modifyQueryUsing: fn(Builder $query, ?Team $record) =>
                //     $query->when($record, function (Builder $query) use ($record) {
                //         $query->where('id', '!=', $record->id)
                //             ->where(function ($query) use ($record) {
                //                 // Only apply the nested subteams condition if a team record exists
                //                 $query->whereNotIn('id', HasTeams::getAllNestedSubteams(Team::find($record->id))->pluck('id'))
                //                     ->orWhereNull('parent_team_id');
                //             });
                //     })
                //     ->orderByRaw('COALESCE(parent_team_id, id), IF(parent_team_id IS NULL, id, parent_team_id), id'))
                //     ->nullable(),

                // TextInput::make('groups')->label('Groepen')
            ]);
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withCount(['users']); // Replace 'relationName' with the actual relation you want to count
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Teamnaam'),
                TextColumn::make('users_count')
                    ->label('Aantal gebruikers')
                    ->counts('users'),

                IconColumn::make('autoassign_rules')
                    ->label('Automatische toewijzing')
                    ->getStateUsing(fn($record) => $record->autoassign_rules === null ? '0' : '1')  // Default state based on null or not null
                    ->icon(fn(string $state): string => match ($state) {
                        '0' => 'heroicon-o-x-circle',  // For null or empty
                        '1' => 'heroicon-o-check-circle',  // For not null
                    })
                    ->color(fn(string $state): string => match ($state) {
                        '0' => 'gray',
                        '1' => 'success',
                    })
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('Leden')
                        ->icon(fn(Team $record): string => $record->autoassign_rules ? 'heroicon-m-user' : 'heroicon-m-user-plus') // Dynamic icon based on 'some_column'
                        ->url(
                            fn(Team $record): string => static::getUrl('team-users.index', [
                                'parent' => $record->id,
                            ])
                        )
                        ->visible(function ($record) {
                            return true;
                        }),
                    Tables\Actions\EditAction::make(),

                    Action::make('remove')
                        ->label('Verwijderen')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->action(function (Team $record) {
                            $record->delete();
                        })
                        ->requiresConfirmation()
                        ->visible(function (Team $record) {
                            return $record->users()->count() === 0 && $record->id !== config('app.system_team_id');
                        }),

                ])
            ])
            ->filters([])
            ->recordClasses(fn(Model $record) => match (true) {
                default => $record->classname,
            });
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
            'index' => Pages\ListTeams::route('/'),
            // 'create' => Pages\CreateTeam::route('/create'),
            // 'edit' => Pages\EditTeam::route('/{record}/edit'),

            // Users 
            'team-users.index' => ListTeamUsers::route('/{parent}/users'),
            'team-users.create' => CreateTeamUser::route('/{parent}/users/create'),
            'team-users.edit' => EditTeamUser::route('/{parent}/users/{record}/edit'),
        ];
    }

    public static function getRecordTitle(?Model $record): string|null|Htmlable
    {
        return $record->name ?? '';
    }

    public static function getTableQuery(): Builder {}

}
