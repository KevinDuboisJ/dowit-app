<?php

namespace App\Filament\Resources\TaskPlannerResource\Pages;

use App\Filament\Resources\TaskPlannerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CampusEnum;
use App\Enums\TaskPlannerFrequency;
use App\Enums\TaskTypeEnum;
use App\Models\TaskPlanner;

class CreateTaskPlanner extends CreateRecord
{
    protected static string $resource = TaskPlannerResource::class;
    protected static bool $canCreateAnother = false;
    public ?string $source = null;

    public function mount(): void
    {
        parent::mount();

        $this->source = request()->query('source');

        if (request()->get('source') === 'Revalidatie') {
            // Get the default values defined in the resource form

            $defaultValues = $this->form->getRawState();
            $overrides = [
                'next_run_at'  => now()->addMinute(),
                'name'         => TaskTypeEnum::PatientTransportInBed->getLabel(),
                'frequency'    => TaskPlannerFrequency::Weekdays->name,
                'task_type_id' => TaskTypeEnum::PatientTransportInWheelchair->value,
                'campus_id'    => CampusEnum::CA->value,
            ];

            // Merge defaults with overrides
            $this->form->fill(array_merge($defaultValues, $overrides));
        }

        if (request()->has('duplicate_from')) {
            $model = TaskPlanner::findOrFail(request('duplicate_from'));

            // Load attributes
            $data = $model->toArray();

            // Optional adjustments:
            $data['next_run_at'] = now()->addMinute();
            unset($data['id']);

            // Fill the form BEFORE render
            $this->form->fill($data);
        }
    }
}
