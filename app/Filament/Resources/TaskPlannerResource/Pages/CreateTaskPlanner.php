<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Filament\Resources\TaskPlannerResource;
use App\Services\TaskPlannerService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTaskPlanner extends CreateRecord
{
    protected static string $resource = TaskPlannerResource::class;
    protected static bool $canCreateAnother = false;

}
