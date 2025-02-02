<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskTypeResource\Pages;
use App\Models\TaskType;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Clusters\TaskConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class TaskTypeResource extends Resource
{
    protected static ?string $model = TaskType::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-2-stack';

    protected static ?string $modelLabel = 'Taaktype';

    protected static ?string $pluralModelLabel = 'Taaktypen';

    protected static ?string $navigationGroup = 'Taakconfigurator';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                ->label('Naam'), 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Naam'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListTaskTypes::route('/'),
        ];
    }
}
