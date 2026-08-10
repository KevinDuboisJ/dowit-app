<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TaskPlannerResource;
use Filament\Pages\Dashboard;

class AdminLanding extends Dashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->redirect(TaskPlannerResource::getUrl(), navigate: true);
    }
}
