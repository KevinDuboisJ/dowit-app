<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskStatus;
use Illuminate\Support\Carbon;

class StoreTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust authorization logic as needed
    }

    public function rules()
    {
       
        return [
            'name' => 'required|string|max:255',
            'description' => 'string|max:500',
            'startDateTime' => 'required|date',
            'taskType' => 'required|exists:task_types,id',
            'campus' => 'required|exists:campuses,id',
            'patient' => 'sometimes|array',
            'patient.pat_id'  => [
                'sometimes',
                'required_with:patient',
                'required_if:taskType,1', // Only required if taskType equals 1 and the key is present.
                'string',
            ],
            'patient.*'  => [
                'string',
            ],
            'space' => 'array',
            'space.*.value' => 'required|exists:spaces.spaces,id',
            'spaceTo' => 'array|min:0',
            'spaceTo.*.value' => 'required|exists:spaces.spaces,id',
            'assignTo' => 'array|min:0',
            'assignTo.*.value' => 'required|exists:users,id',
        ];
    }

    public function prepareForDatabase()
    {
        $validated = $this->validated();

        $data = [
            'task' => [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'start_date_time' => Carbon::parse($validated['startDateTime'])->setTimezone(config('app.timezone')),
                'task_type_id' => $validated['taskType'],
                'campus_id' => $validated['campus'],
                'space_id' => !empty($validated['space']) ? array_column($validated['space'], 'value')[0] : null,
                'space_to_id' => !empty($validated['spaceTo']) ? array_column($validated['spaceTo'], 'value')[0] : null,
                'status_id' => TaskStatus::Added->value,
                'is_active' => true,
            ]
        ];
        logger($validated);
        if (isset($validated['patient'])) {
            $data['patient'] = $validated['patient'];
        }

        return $data;
    }
}
