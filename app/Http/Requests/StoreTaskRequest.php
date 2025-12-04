<?php

namespace App\Http\Requests;

use App\Enums\TaskTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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
            'description' => 'string',
            'startDateTime' => 'required|date',
            'taskType' => [
                'required',
                'in:' . implode(',', array_column(TaskTypeEnum::cases(), 'name'))
            ],
            'campus' => 'required|exists:campuses,id',
            'visit' => 'sometimes|array',
            'visit.id'  => [
                'sometimes',
                'required_with:visit',
                Rule::requiredIf(function () {
                    return TaskTypeEnum::fromCaseName($this->input('taskType'))->isPatientTransport();
                }),
                'numeric',
            ],
            'tags' => 'array',
            'space' => 'array',
            'space.*.value' => 'required|exists:spaces.spaces,id',
            'spaceTo' => 'array|min:0',
            'spaceTo.*.value' => 'required|exists:spaces.spaces,id',
            'assignees' => 'array',
            'teamsMatchingAssignment' => 'array',
             // 'assets' => 'array',

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
                'task_type_id' => TaskTypeEnum::fromCaseName($validated['taskType'])->value,
                'campus_id' => $validated['campus'],
                'space_id' => !empty($validated['space']) ? array_column($validated['space'], 'value')[0] : null,
                'space_to_id' => !empty($validated['spaceTo']) ? array_column($validated['spaceTo'], 'value')[0] : null,
            ]
        ];

        if (isset($validated['tags'])) {
            $data['tags'] = $validated['tags'];
        }

        if (isset($validated['visit'])) {
            $data['visit'] = $validated['visit'];
        }

        if (isset($validated['assignees'])) {
            $data['assignees'] = $validated['assignees'];
        }

        if (isset($validated['teamsMatchingAssignment'])) {
            $data['teamsMatchingAssignment'] = $validated['teamsMatchingAssignment'];
        }

        return $data;
    }
}
