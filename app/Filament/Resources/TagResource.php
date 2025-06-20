<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Filament\Resources\TagResource\RelationManagers;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Filament\Tables\Columns\TextColumn;
use Guava\FilamentIconPicker\Tables\IconColumn;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\ColorColumn;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    // protected static ?string $modelLabel = 'Taaktype';

    // protected static ?string $pluralModelLabel = 'Taaktypen';

    protected static ?string $navigationGroup = 'Taakconfigurator';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Naam'),

                ColorPicker::make('bg_color')
                    ->label('Kleur kiezen') // optional label
                    ->nullable(),

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


            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->width('300px'),

                ColorColumn::make('bg_color')
                    ->label('Kleur')
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
                    //Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTags::route('/'),
            //'create' => Pages\CreateTag::route('/create'),
            //'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
