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
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;

class GeneralSetting extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Algemene instellingen';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.general-setting';
    protected static ?string $navigationLabel = 'Algemene instellingen';
    protected static ?string $navigationGroup = 'Instellingen';

    // Property to hold settings values
    public ?array $settings = [];

    public function mount(): void
    {
        // Retrieve settings from the database and set them to $settings
        $this->settings = Setting::where('type', 'global')->pluck('value', 'code')->toArray();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->schema(fn() => $this->generateDynamicFields()),
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

        foreach ($data['settings'] as $code => $value) {
            $setting = Setting::where('code', $code)->first();
            if ($setting) {
                $setting->update(['value' => $value]);
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

                // Define the order of priority
                $priorityOrder = ["Low", "Medium", "High"];

                // Reorder the array based on priority
                $inputGroup = array_replace(
                    array_flip($priorityOrder), // Create an ordered array with keys and null values
                    $inputGroup // Merge with the original array to preserve values
                );

                // Handle task priority colors dynamically
                $result[] = Section::make("Taak prioriteit")
                    ->schema(array_map(function ($field, $index) use ($code) {

                        return Grid::make(12)
                            ->schema([
                                Placeholder::make('text')
                                    ->label(__("Settings.$index") . ':') // Displayed as the label for the static text
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
                    ->description('Deze instelling bepaalt de standaard taakprioriteit en kleur als er geen prioriteit handmatig is toegewezen. De kleur hangt af van hoe lang geleden de taak is aangemaakt');
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
