<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Enums\TeamEnum;
use App\Filament\Resources\TaskPlannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class ListTaskPlanners extends ListRecords implements HasForms
{
    use InteractsWithForms;

    public ?Task $taskBeingEdited = null;
    public ?array $data = [];

    protected static string $resource = TaskPlannerResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();

        // Base create actions
        $actions = [
            Actions\CreateAction::make()
                ->label('Nieuw')
                ->groupedIcon('')
        ];

        // Conditionally add extra action if user has certain team
        if ($user->teams->contains('id', TeamEnum::Revalidatie->value)) {
            $actions[] = Actions\CreateAction::make('Revalidatie')
                ->label('Kine/Ergo CA Taakplanner')
                ->groupedIcon('')
                ->url(fn() => TaskPlannerResource::getUrl('create', [
                    'source' => 'Revalidatie',
                ]));
        }

        if (count($actions) > 1) {
            return [
                Actions\ActionGroup::make($actions)
                    ->label('Taakplanner aanmaken')
                    ->iconPosition(IconPosition::After)
                    ->button()
                    ->color('primary'),
            ];
        }

        return [
            Actions\CreateAction::make()
                ->label('Taakplanner aanmaken')
                ->createAnother(false)
        ];
    }


    public function openEditTaskModal($taskId): void
    {
        $this->taskBeingEdited = Task::findOrFail($taskId);
        $this->form->fill([
            'start_date_time' => $this->taskBeingEdited->start_date_time,
        ]);

        $this->dispatch('hide-edit-task-loading', id: $taskId);
        $this->dispatch('open-modal', id: 'edit-task-modal');
    }

    public function openDeleteTaskModal($taskId): void
    {
        $this->taskBeingEdited = Task::findOrFail($taskId);
        $this->dispatch('hide-edit-task-loading', id: $taskId);
        $this->dispatch('open-modal', id: 'delete-task-modal');
    }

    public function updateTask(): void
    {
        $this->taskBeingEdited->update($this->form->getState());

        \Filament\Notifications\Notification::make()
            ->title('Opgeslagen')
            ->success()
            ->send();

        $this->dispatch('$refresh');
        $this->dispatch('close-modal', id: 'edit-task-modal');
    }

    public function deleteTask(): void
    {
        $this->taskBeingEdited->delete();

        \Filament\Notifications\Notification::make()
            ->title('Verwijderd')
            ->success()
            ->send();

        $this->dispatch('$refresh');
        $this->dispatch('close-modal', id: 'delete-task-modal');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DateTimePicker::make('start_date_time')
                    ->label('Startdatum')
                    ->required()
                    ->after(now()),
            ])
            ->statePath('data');
    }
}
