<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Filament\Resources\HolidayResource\RelationManagers;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;


class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Feestdag';

    protected static ?string $pluralModelLabel = 'Feestdagen';

    protected static ?int $navigationSort = 18;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                DatePicker::make('date')
                    ->required()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam'),
                TextColumn::make('date')
                    ->label('Datum'),
                IconColumn::make('public')
                    ->label('Wettelijke feestdag')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Bewerken')
                    ->visible(function ($record) {
                        return !$record->public;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Verwijderen')
                    ->visible(function ($record) {
                        return !$record->public;
                    }),

            ])
            ->filters([
                // Add any filters needed
            ]);
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
            'index' => Pages\ListHolidays::route('/'),
            // 'create' => Pages\CreateHoliday::route('/create'),
            // 'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
