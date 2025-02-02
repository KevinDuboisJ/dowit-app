<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Livewire\Component;
use App\Models\Team;
use Exception;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;

class TeamSetting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.team-setting';
    protected static ?string $navigationLabel = 'Teaminstellingen';
    protected static ?string $navigationGroup = 'Instellingen';

    public int $team_id;
    public Team $team;
    public ?array $settings = [];
    public array $defaultSettings = [];
    public array $settingNameArray = [];

    public function mount(): void
    {
        // Load default setting
        $defaultSettingQueryBuilder = Setting::select('value', 'code', 'name');
        $this->settingNameArray = $defaultSettingQueryBuilder->pluck('name', 'code')->toArray();
        $this->defaultSettings =  $defaultSettingQueryBuilder->pluck('value', 'code')->toArray();

        // Set default team
        $this->setTeam();
    }

    protected function setTeam($teamId = null)
    {
        if (!$teamId) {
            // Get default team id
            $teamId = Auth::user()->getDefaultTeam()->id;
        }

        // Set the team
        $this->team = Team::with('settings')->find($teamId);

        // Set the team_id
        $this->team_id = $this->team->id;

        // Merge team-specific overrides with defaults
        $this->settings = $this->team->settings->toArray();
    }

    // #[On('refreshForm')]
    // public function refresh(): void {}

    public function form(Form $form): Form
    {

        return $form->schema([
            Select::make('team_id')
                ->label('Kies een team')
                ->options(Auth::user()->teams->pluck('name', 'id')->toArray())
                ->required()
                ->live()
                ->default($this->team)
                ->selectablePlaceholder(false)
                ->afterStateUpdated(fn(String $state, Component $livewire,) => $this->setTeam($state)),

            Section::make()->schema(fn() => $this->generateDynamicFields()),
        ]);
    }

    public function getFormAction(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save()
    {
        $data = $this->form->getState();

        foreach ($data['settings'] as $code => $newSettingArray) {
            $setting = Setting::where('code', $code)->first();

            if ($code === 'TASK_PRIORITY') {
                // Custom handling for task priority colors
                $mergedSettings = [];
                foreach ($newSettingArray as $key => $value) {
                    $mergedSettings[$key] = [
                        'time' => $value['time'],
                        'color' => $value['color'],
                    ];
                }
                $this->team->settings()->syncWithoutDetaching([
                    $setting->id => ['value' => $mergedSettings],
                ]);
            } else {
                // Default handling for other settings
                $defaultSettingArray = $this->defaultSettings[$code] ?? throw new Exception("Error: Contact support.");

                foreach ($defaultSettingArray as $index => $item) {
                    if (isset($newSettingArray[$index])) {
                        $newSettingArray[$index] = array_merge($item, $newSettingArray[$index]);
                    }
                }

                if ($newSettingArray !== $defaultSettingArray) {
                    $this->team->settings()->syncWithoutDetaching([$setting->id => ['value' => $newSettingArray]]);
                } else {
                    $this->team->settings()->detach($setting->id);
                }
            }
        }

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }

    protected function generateDynamicFields()
    {
        $result = [];
        foreach ($this->settings as $code => $inputGroup) {

            if ($code === 'TASK_PRIORITY') {

                // Handle task priority colors dynamically
                $result[] = Section::make("Taak prioriteit")
                    ->schema(array_map(function ($field, $index) use ($code) {

                        return Grid::make(12)
                            ->schema([
                                Placeholder::make('text')
                                    ->label(__("settings.$index") . ':') // Displayed as the label for the static text
                                    ->columnSpan(2), // Takes up one column
                                TextInput::make("settings.$code.$index.time")
                                    ->label(__('Tijd (minuten)')) // Translate "Time (minutes)" if needed
                                    ->numeric()
                                    ->default($field['time'])
                                    ->columnSpan(5),
                                ColorPicker::make("settings.$code.$index.color")
                                    ->label(__('Kleur')) // Translate "Color" if needed
                                    ->default($field['color'])
                                    ->columnSpan(5),
                            ]);
                    }, $inputGroup, array_keys($inputGroup)))
                    ->label('Taak prioriteit')
                    ->description('De kleur die wordt toegepast op een taak hangt af van of deze minder dan het opgegeven aantal minuten geleden is aangemaakt');
            } else {
                // Default handling for other settings
                $result[] = Section::make()->schema(
                    array_map(function ($field, $index) use ($code) {
                        return match ($field['type']) {
                            'text' => TextInput::make("settings.$code.$index.value")
                                ->label($field['label'])
                                ->default($field['value']),
                            'color' => ColorPicker::make("settings.$code.$index.value")
                                ->label(__('Kleur')) // Translate "Color" if necessary
                                ->default($field['value']),
                            default => TextInput::make("settings.$code.$index.value")
                                ->label($field['label'])
                                ->default($field['value']),
                        };
                    }, $inputGroup, array_keys($inputGroup))
                )->label($this->settingNameArray[$code]);
            }
        }

        return $result;
    }
}
