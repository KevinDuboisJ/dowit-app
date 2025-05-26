<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Filament\Resources\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Bestand toevoegen')
                ->closeModalByClickingAway(false)
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
}
