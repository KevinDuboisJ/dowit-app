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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;

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

                IconColumn::make('is_online')
                    ->label('Online')
                    ->boolean()
                    ->getStateUsing(
                        fn(User $record): bool =>
                        $record->last_seen_at?->gt(now()->subMinutes(2)) ?? false
                    ),

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

                TextColumn::make('last_login_at')
                    ->label('Laatste aanmelding')
                    ->dateTime('d/m/Y H:m:s')
                    ->sortable(),

                TextColumn::make('last_seen_at')
                    ->label('Laatst actief')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('last_logout_at')
                    ->label('Laatste afmelding')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('inactive')
                    ->label('Inactieve gebruikers')
                    ->query(fn(Builder $query): Builder => $query->where('is_active', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Pincode')
                    ->visible(function ($record) {
                        return is_null($record->object_sid);
                    }),
            ])
            ->bulkActions([])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('firstname', 'asc')
                    ->orderBy('lastname', 'asc');
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
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
