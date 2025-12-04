<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Traits\HasAccessScope;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use App\Traits\HasTeams;


class UserResource extends Resource
{
    use HasTeams, HasAccessScope;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $modelLabel = 'Gebruiker';

    protected static ?string $pluralModelLabel = 'Gebruikers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('password')
                    ->label('PIN code')
                    ->password()
                    ->required()
                    ->rule(['integer', 'digits_between:3,10'])
                    ->revealable()
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->whereNot('id', 1))
            ->columns([
                TextColumn::make('firstname')
                    ->label('Voornaam')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lastname')
                    ->label('Achternaam')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('username')
                    ->label('Gebruikersnaam')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('teams.name')
                    ->label('Teams')
                    ->limit(30) // Truncate display after 20 characters
                    ->tooltip(fn($state) => is_array($state) ? implode(', ', $state) : (string) $state),

                TextColumn::make('last_login')
                    ->label('Laatste aanmelding')
                    ->datetime('d/m/Y H:m:s')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Pincode')
                    ->visible(function ($record) {
                        return is_null($record->object_sid);
                    }),
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
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
