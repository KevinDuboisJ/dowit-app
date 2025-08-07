<?php
// app/Filament/Pages/AbstractSettingsPage.php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Config;
use App\Services\SettingService;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;

class TeamSetting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Teaminstellingen ';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.team-setting';
    protected static ?string $navigationLabel = 'Teaminstellingen ';
    protected static ?string $navigationGroup = 'Instellingen';

    /** Scope: either 'global' or 'team' */
    protected static string $scope = 'team';

    public array $data = [];
    protected SettingService $settingService;

    /** Map your config type → Filament component */
    protected static array $fieldMap = [
        'text'     => \Filament\Forms\Components\TextInput::class,
        'email'    => \Filament\Forms\Components\TextInput::class,
        'number'   => \Filament\Forms\Components\TextInput::class,
        'textarea' => \Filament\Forms\Components\Textarea::class,
        'select'   => \Filament\Forms\Components\Select::class,
        'color'    => \Filament\Forms\Components\ColorPicker::class,
        'repeater' => \Filament\Forms\Components\Repeater::class,
    ];

    public static function canAccess(): bool
    {
        return (Auth::user()?->isSuperAdmin() || Auth::user()?->isAdmin()) ?? false;
    }

    public function getFormModel(): ?string
    {
        return null; // not binding to a model
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function mount(SettingService $settingService): void
    {
        $this->settingService = $settingService;
        $userDefaultTeamId = Auth::user()->getDefaultTeam()->id;

        // fill initial state
        $this->data = $this->getDefaultSettingsForSelectedTeam($userDefaultTeamId);
        $this->data['team_id'] = $userDefaultTeamId;
        $this->form->fill();
    }

    public function getFormAction(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function form(Form $form): Form
    {

        return $form->schema([
            //------------------------------------------------------------
            // 1) The team dropdown (reactive). When it changes, it sets
            //    $this->data['team_id'], causing Filament to re-render the closure
            //    in the “Dynamic Settings” section below.
            //------------------------------------------------------------
            Select::make('team_id')
                ->label('Kies een team')
                ->options(Auth::user()->teams->pluck('name', 'id')->toArray())
                ->default($this->data['team_id'])
                ->required()
                ->live() // Makes Filament re‐evaluate any closures
                ->selectablePlaceholder(false)
                ->afterStateUpdated(function ($state) {
                    // When the team changes, we fetch the default settings for that team
                    $this->data = array_merge($this->data, $this->getDefaultSettingsForSelectedTeam($state));
                }),

            //------------------------------------------------------------
            // 2) A Section whose `schema` is a closure. Filament will
            //    call this closure every time `$this->data['team_id']` changes,
            //    so inside we can fetch all settings for that team and
            //    return a freshly built array of Form components.
            //------------------------------------------------------------
            Section::make('Instellingen voor geselecteerd team')
                ->schema(fn(): array => $this->generateDynamicFieldsForTeam()),
        ])
            ->statePath('data');
    }

    /**
     * Build an array of Form components based on the DB rows for $this->data['team_id'].
     *
     * @return \Filament\Forms\Components\Component[] 
     */
    protected function generateDynamicFieldsForTeam(): array
    {
        $components = [];

        // 5) Loop door je “settings.definitions” (instellingen uit settings.php)
        foreach (Config::get('settings.definitions') as $code => $def) {
            // a) Alleen tonen als deze scope klopt
            if (! in_array(static::$scope, $def['scopes'], true)) {
                continue;
            }

            // b) Haal de bijbehorende “raw” waarde uit de DB (indien aanwezig)
            //    Bij een groepsveld is dit vaak een JSON‐string die we straks decoderen.
            $rawValue = $this->data[$code] ?? null;

            // c) Indien het geen “group” is, maak één enkel component
            if ($def['type'] !== 'group') {
                // Bepaal eerst welke Filament‐component we gebruiken (text/email/number/color)
                $componentClass = static::$fieldMap[$def['type']] ?? null;

                if ($componentClass) {
                    $field = $componentClass::make($code)
                        ->label($def['label'])
                        ->rules($def['rules'] ?? [])
                        ->default($rawValue);

                    // Type‐shorthand: email() en numeric() indien gewenst
                    if ($def['type'] === 'email') {
                        $field->email();
                    }
                    if ($def['type'] === 'number') {
                        $field->numeric();
                    }

                    $components[] = $field;
                }

                continue;
            }

            // d) Als het wél een “group” is, en specifiek TASK_PRIORITY:
            //    We gaan ervan uit dat voor TASK_PRIORITY in je settings.php
            //    iets staat als:
            //      'type'   => 'group',
            //      'config' => [
            //          'levels' => ['low','medium','high'],
            //          'fields' => [
            //              'time'  => [ 'label'=>'...', 'placeholder'=>'...', 'rules'=>[...] ],
            //              'color' => [ 'label'=>'...', 'rules'=>[...] ],
            //          ],
            //      ],
            //    en dat de DB‐waarde $rawValue een JSON‐string is zoals:
            //      {
            //        "low":    { "time": 48, "color": "#00FF00" },
            //        "medium": { "time": 24, "color": "#FFFF00" },
            //        "high":   { "time": 4,  "color": "#FF0000" }
            //      }
            if ($def['type'] === 'group' && $code === 'TASK_PRIORITY') {
                // Decodeer de JSON naar een array
                $groupValues = $rawValue;

                // Maak voor elk “level” uit de definitie één Grid
                $components[] = Section::make($def['label'] ?? 'Taak prioriteit')->schema(
                    array_map(function ($level) use ($def, $code, $groupValues) {
                        // $groupValues[$level] = ['time'=>..., 'color'=>...]
                        $levelData = $groupValues[$level] ?? [];

                        return Grid::make(12)
                            ->schema([
                                Placeholder::make("{$code}_label_{$level}")
                                    ->label(__("Settings.{$level}") . ':')
                                    ->columnSpan(1),

                                TextInput::make("{$code}.{$level}.time")
                                    ->label($def['config']['fields']['time']['label'])
                                    ->placeholder($def['config']['fields']['time']['placeholder'])
                                    ->rules($def['config']['fields']['time']['rules'])
                                    ->numeric()
                                    ->default($levelData['time'] ?? null)
                                    ->columnSpan(4),

                                ColorPicker::make("{$code}.{$level}.color")
                                    ->label($def['config']['fields']['color']['label'])
                                    ->rules($def['config']['fields']['color']['rules'])
                                    ->default($levelData['color'] ?? null)
                                    ->columnSpan(4),
                            ]);
                    }, $def['config']['levels'])
                )->columns(3);

                continue;
            }
        }

        return $components;
    }


    public function save(SettingService $settingService): void
    {
        $data = $this->form->getState();
        $teamId = $data['team_id'];

        unset($data['team_id']);

        foreach ($data as $code => $value) {
            $settingService->set($code, $value, static::$scope, $teamId);
        }

        Notification::make()
            ->title('Opgeslagen')
            ->success()
            ->send();
    }

    public function getDefaultSettingsForSelectedTeam($teamId): array
    {
        $settingsService = app(SettingService::class);
        $data = [];

        foreach (Config::get('settings.definitions') as $code => $def) {
            if (! in_array(static::$scope, $def['scopes'], true)) {
                continue;
            }
            $data[$code] = $settingsService->get($code, $teamId, $def['default']);
        }

        return $data;
    }
}
