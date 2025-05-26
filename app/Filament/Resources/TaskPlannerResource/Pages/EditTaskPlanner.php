<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Enums\TaskPlannerFrequency;
use App\Filament\Resources\TaskPlannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use App\Services\TaskPlannerService;

class EditTaskPlanner extends EditRecord
{
    protected static string $resource = TaskPlannerResource::class;

    protected ?string $heading =  'Ingeplande taak bewerken';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

}
