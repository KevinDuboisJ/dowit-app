<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Enums\TaskPlannerFrequency;
use App\Filament\Resources\TaskPlannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskPlanner extends EditRecord
{
    protected static string $resource = TaskPlannerResource::class;

    protected ?string $heading =  'Ingeplande taak bewerken';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
