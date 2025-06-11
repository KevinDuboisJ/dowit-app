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
use Filament\Forms\Form;

class GlobalSetting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Algemene instellingen';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.global-setting';
    protected static ?string $navigationLabel = 'Algemene instellingen';
    protected static ?string $navigationGroup = 'Instellingen';

    /** Scope: either 'global' or 'team' */
    protected static string $scope = 'global';

    public array $data = [];
    protected ?int $teamId = null;
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
        return Auth::user()->isSuperAdmin();
    }

    public function mount(SettingService $settingService): void
    {

        $this->settingService = $settingService;

        if (static::$scope === 'team') {
            $this->teamId = Auth::user()->getDefaultTeam();
        }

        // fill initial state
        $initial = [];
        foreach (Config::get('settings.definitions') as $code => $def) {
            if (! in_array(static::$scope, $def['scopes'], true)) {
                continue;
            }
            $initial[$code] = $this->settingService->get($code, $this->teamId, $def['default']);
        }

        $this->data = $initial;
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
        $schema = [];

        foreach (Config::get('settings.definitions') as $code => $def) {

            $field = null;

            if (! in_array(static::$scope, $def['scopes'], true)) {
                continue;
            }

            $class = static::$fieldMap[$def['type']] ?? null;

            if ($class) {
                $field = $class::make($code)
                    ->label($def['label'])
                    ->rules($def['rules'] ?? []);
            }

            // shorthand modifiers:
            if ($def['type'] === 'email') {
                $field->email();
            }
            if ($def['type'] === 'number') {
                $field->numeric();
            }

            if ($def['type'] === 'group' && $code === 'TASK_PRIORITY') {

                $field = \Filament\Forms\Components\Section::make($def['label'])
                    ->schema(
                        array_map(function ($level) use ($def, $code) {

                            return \Filament\Forms\Components\Grid::make(12)
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('text')
                                        ->label(__("Settings.$level") . ':')
                                        ->columnSpan(2),

                                    \Filament\Forms\Components\TextInput::make("$code.$level.time")
                                        ->label($def['config']['fields']['time']['label'])
                                        ->placeholder($def['config']['fields']['time']['placeholder'])
                                        ->rules($def['config']['fields']['time']['rules'])
                                        ->numeric()
                                        ->columnSpan(2),

                                    \Filament\Forms\Components\ColorPicker::make("$code.$level.color")
                                        ->label($def['config']['fields']['color']['label'])
                                        ->rules($def['config']['fields']['color']['rules'])
                                        ->columnSpan(2),
                                ]);
                        }, $def['config']['levels'])
                    );
            }

            if ($field) {
                $schema[] = $field;
            }
        }

        return $form->schema($schema)->statePath('data');
    }

    public function flattenSettingsArray(array $settings)
    {
        $result = [];
        foreach ($settings as $code => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $kk => $vv) {
                            $result["{$code}.{$k}.{$kk}"] = $vv;
                        }
                    } else {
                        $result["{$code}.{$k}"] = $v;
                    }
                }
            } else {
                $result[$code] = $value;
            }
        }
        return $result;
    }

    public function save(SettingService $settingService): void
    {
        $data = $this->form->getState();
        foreach ($data as $code => $value) {
            $settingService->set($code, $value, static::$scope, $this->teamId);
        }
        Notification::make()
            ->title('Opgeslagen')
            ->success()
            ->send();
    }
}
