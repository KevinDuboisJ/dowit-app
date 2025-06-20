<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChainResource\Pages;
use App\Models\Chain;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;

use App\Filament\Resources\ChainResource\Pages\CreateChainWizard;

class ChainResource extends Resource
{
    protected static ?string $model = Chain::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Taakconfigurator';

    protected static ?string $modelLabel = 'Keten';

    protected static ?string $pluralModelLabel = 'ketens';

    protected static ?int $navigationSort = 2;

    protected static array $spacesCache = [];
    protected static array $campusesCache = [];
    protected static array $taskTypesCache = [];

    public static function cacheLookupsForPage(array $spaceIds, array $campusIds, array $taskTypeIds): void
    {
        static::$spacesCache = \App\Models\Space::whereIn('id', array_unique($spaceIds))->get()->keyBy('id')->toArray();
        static::$campusesCache = \App\Models\Campus::whereIn('id', array_unique($campusIds))->get()->keyBy('id')->toArray();
        static::$taskTypesCache = \App\Models\TaskType::whereIn('id', array_unique($taskTypeIds))->get()->keyBy('id')->toArray();
    }


    public static function table(Table $table): Table
    {

        return $table->columns([
            TextColumn::make('identifier')->label('Identificatie')
                ->description(fn(Chain $record): string => $record->description),
            TextColumn::make('actions')
                ->label('Acties')
                ->formatStateUsing(function (Model $record) {

                    $text = '';

                    if (empty($record->actions)) {
                        return new HtmlString('Geen actie gevonden.');
                    }

                    if (isset($record->actions['createTask'])) {
                        $task = $record->actions['createTask'];

                        $space    = \App\Filament\Resources\ChainResource::$spacesCache[$task['space_id'] ?? null] ?? null;
                        $campus   = \App\Filament\Resources\ChainResource::$campusesCache[$task['campus_id'] ?? null] ?? null;
                        $taskType = \App\Filament\Resources\ChainResource::$taskTypesCache[$task['task_type_id'] ?? null] ?? null;

                        $description = data_get($task, 'description.content.0.content.0.text');
                        $text = '<h4 class="text-green-800">1. Taak aanmaken</h4>';
                        $text .= '<div class="ml-4">';
                        $text .= collect([
                            'Locatie'      => $space ? "{$space['name']} ({$space['_spccode']})" : '-',
                            'Campus'       => $campus['name'] ?? '-',
                            'Taaktype'     => $taskType['name'] ?? '-',
                            'Omschrijving' => \Illuminate\Support\Str::limit($description, 50),
                        ])
                            ->map(fn($v, $k) => "<strong>$k:</strong> $v")
                            ->implode('<br>');

                        $text .= '</div>';
                    }

                    if (!empty($record->actions['code'])) {
                        $text = '<h4 class="text-green-800">1. Code</h4>';
                        $text .= '<div class="ml-4">';
                        $text .= "<strong>Class:</strong> {$record->actions['code']['code']}";
                        $text .= '</div>';
                    }

                    return new HtmlString($text);
                }),
            // TextColumn::make('actions')->label('Acties')->listWithLineBreaks(),
            TextColumn::make('trigger_type')->label('Triggertype'),
            IconColumn::make('is_active')
                ->label('Active')
                ->boolean(),
        ])->openRecordUrlInNewTab();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChains::route('/'),
            'create' => CreateChainWizard::route('/create'),
            'edit'   => CreateChainWizard::route('/{record}/edit'),
        ];
    }
}
