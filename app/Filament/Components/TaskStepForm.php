<?php

namespace App\Filament\Components;

use Livewire\Component;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Models\Campus;
use App\Models\Space;
use App\Models\TaskType;

class TaskStepForm extends Component implements HasForms
{
    use InteractsWithForms;

    protected $listeners = ['validate-task-steps' => 'runValidation'];
    public int $stepIndex;
    public array $taskData = [];
    

    public function mount(array $data = []): void
    {
        $this->form->fill([
            'taskData' => $this->taskData,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('taskData.task_name')
                ->label('Naam')
                ->required(),

            Textarea::make('taskData.task_description')
                ->label('Omschrijving')
                ->nullable(),

            Select::make('taskData.task_type_id')
                ->label('Taaktype')
                ->options(TaskType::pluck('name', 'id'))
                ->required()
                ->preload(),

            Select::make('taskData.campus_id')
                ->label('Campus')
                ->options(Campus::pluck('name', 'id'))
                ->required()
                ->preload(),

            Select::make('taskData.space_id')
                ->label('Locatie')
                ->searchable(['name', '_spccode'])
                ->getSearchResultsUsing(function (string $search) {
                    return Space::query()
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('_spccode', 'like', "%{$search}%")
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn($space) => [
                            $space->id => "{$space->name} ({$space->_spccode})",
                        ]);
                })
                ->getOptionLabelUsing(fn($value) => optional(Space::find($value))->name ?? $value)
                ->required()
                ->preload(),
        ];
    }

    public function runValidation()
    {
        $this->validate();

        // Notify parent (CreateTaskChainWizard) that validation was successful
        $this->dispatch('step-valid', stepIndex: $this->stepIndex);
    }

    public function updated($property): void
    {
        // When any form field changes, emit updated taskData to the parent component
        $this->emitUp('updateStepData', $this->stepIndex, $this->taskData);
    }

    public function render()
    {
        return view('filament.components.task-step-form');
    }
}
