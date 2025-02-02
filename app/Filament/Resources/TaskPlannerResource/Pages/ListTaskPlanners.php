<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Filament\Resources\TaskPlannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\TaskType;
use App\Jobs\CreateTasksJob;
use Illuminate\Support\Carbon;
use App\Models\TaskPlanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ListTaskPlanners extends ListRecords
{
    protected static string $resource = TaskPlannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Taak inplannen')
                ->createAnother(false),

            // Actions\Action::make('createModelA')
            //     ->label('Type taak aanmaken')
            //     ->closeModalByClickingAway(false)
            //     ->form([
            //         TextInput::make('name')
            //             ->label('naam')
            //             ->required(), // Fields for Model A
            //         Select::make('team_id')
            //             ->label('Team')
            //             ->relationship('team', 'name'),
            //     ])
            //     ->action(function (array $data) {
            //         TaskType::create($data);

            //         // Notification::make()
            //         //     ->title('Model A created successfully!')
            //         //     ->success()
            //         //     ->send();
            //     })
            //     ->modalContent(fn() => $this->renderModelATable()), // Render table below the form,
        ];
    }

    /**
     * Method to render the table below the form
     */
    // protected function renderModelATable()
    // {
    //     return view('components.model-a-table', [
    //         'records' => TaskType::all(),  // Retrieve the records for Model A
    //     ]);
    // }
}
