<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use App\Traits\HasFilamentTeamFields;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 19;

    protected static ?string $modelLabel = 'Bestand';

    protected static ?string $pluralModelLabel = 'Bestanden';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([
                        TextInput::make('name')
                            ->label('Naam')
                            ->required(),

                        TextInput::make('link')
                            ->label('Link')
                            ->required(),

                        Select::make('taskTypes')
                            ->label('Taaktypes')
                            ->preload()
                            ->relationship(name: 'taskTypes', titleAttribute: 'name')
                            ->multiple(),

                        Select::make('tags')
                            ->label('Tags')
                            ->preload()
                            ->relationship(name: 'tags', titleAttribute: 'name')
                            ->multiple(),

                    ])
                    ->columnSpan(2),

                Section::make('')
                    ->schema(function (?Model $record) {

                        return [
                            HasFilamentTeamFields::belongsToTeamsField(),
                        ];
                    })
                    ->columnSpan(1),

                HasFilamentTeamFields::creatorField(),

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam'),
                TextColumn::make('teams.name')->label('Naam'),
                TextColumn::make('link'),
                TextColumn::make('tasktypes.name')->label('Taaktype'),
                TextColumn::make('tags.name')->label('Tags')->badge()
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Bewerken'),

                    DeleteAction::make(),
                ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            // 'create' => Pages\CreateAsset::route('/create'),
            // 'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
