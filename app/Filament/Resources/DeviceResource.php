<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'Toestellen';

    protected static ?string $modelLabel = 'Toestel';

    protected static ?string $pluralModelLabel = 'Toestellen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('identifier')
                    ->label('Identificator')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Beschrijving')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('type')
                    ->label('Type toestel')
                    ->options([
                        'gsm' => 'GSM',
                        'poetskar' => 'Poetskar',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Checkbox::make('is_registered')
                    ->label('Geregistreerd'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('identifier')
                    ->label('Identificator')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'gsm' => 'GSM',
                        'poetskar' => 'Poetskar',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschrijving')
                    ->limit(50),


                Tables\Columns\TextColumn::make('last_used_by')
                    ->label('Laatst gebruikt door')
                    ->formatStateUsing(function ($state, Device $record): string {
                        $user = $record->lastUsedBy;

                        return $user
                            ? trim("{$user->firstname} {$user->lastname}")
                            : '-';
                    }),


                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Laatst gebruikt op')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('is_registered')
                    ->label('Registratie')
                    ->options([
                        '1' => 'Geregistreerd',
                        '0' => 'Niet geregistreerd',
                    ])
                    ->default('1')
                    ->selectablePlaceholder(false)
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? '1';

                        return match ($value) {
                            '1' => $query->where('is_registered', true),
                            '0' => $query->where('is_registered', false),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $value = $data['value'] ?? '1';

                        return match ($value) {
                            '0' => 'Registratie: Niet geregistreerd',
                            default => null,
                        };
                    }),
            ])
            ->defaultSort('identifier')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Bewerken')
                    ->modalHeading('Toestel bewerken'),

                Tables\Actions\DeleteAction::make()
                    ->label('Verwijderen'),
            ])

            ->modifyQueryUsing(
                fn($query) => $query
                    ->with('lastUsedBy')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
        ];
    }
}
