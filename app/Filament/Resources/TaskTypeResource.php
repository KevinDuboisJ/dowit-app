<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskTypeResource\Pages;
use App\Models\TaskType;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class TaskTypeResource extends Resource
{
    protected static ?string $model = TaskType::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

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

                IconPicker::make('icon')
                ->label('Icoon')
                ->placeholder('Selecteren')
                ->sets(['taskicons'])
                ->cacheable(false)
                ->preload()
                ->itemTemplate(fn($component, $icon) => view('filament.components.icon-picker', [
                    'statePath' => $component->getStatePath(),
                    'icon' => $icon,
                ])->render())
                ->extraAttributes([
                    'class' => '!bg-transparent !border-none !shadow-none !focus:ring-0 !ring-0 !focus:border-none !max-h-[6px]',
                ])
                ->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Naam')
                ->width('300px'),

                IconColumn::make('icon')
                    ->label('Icoon')
                    ->view('filament.components.icon-picker-column')

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
