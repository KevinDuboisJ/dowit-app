<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Filament\Resources\TaskPlannerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CampusEnum;
use App\Enums\TaskPlannerFrequency;
use App\Enums\TaskTypeEnum;

class CreateTaskPlanner extends CreateRecord
{
    protected static string $resource = TaskPlannerResource::class;
    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        if (request()->get('source') === 'kineCA') {
            // Get the default values defined in the resource form

            $defaultValues = $this->form->getRawState();

            $overrides = [
                'frequency'   => TaskPlannerFrequency::Daily->name,
                'task_type_id' => TaskTypeEnum::PatientTransportInBed->value,
                'campus_id'   => CampusEnum::CA->value,
            ];

            // 3. Merge defaults with overrides
            $this->form->fill(array_merge($defaultValues, $overrides));
        }
    }
}
