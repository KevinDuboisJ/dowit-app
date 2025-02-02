<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TaskConfigurator;
use App\Filament\Resources\TaskChainResource\Pages;
use App\Filament\Resources\TaskChainResource\RelationManagers;
use App\Models\TaskChain;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskChainResource extends Resource
{
    protected static ?string $model = TaskChain::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Taakconfigurator';

    protected static ?string $modelLabel = 'Ketens';

    protected static ?string $pluralModelLabel = 'kettingen';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListTaskChains::route('/'),
            'create' => Pages\CreateTaskChain::route('/create'),
            'edit' => Pages\EditTaskChain::route('/{record}/edit'),
        ];
    }
}
