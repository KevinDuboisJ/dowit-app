<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Filament\Resources\TaskPlannerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskPlanner extends CreateRecord
{
    protected static string $resource = TaskPlannerResource::class;
    protected static bool $canCreateAnother = false;
}
