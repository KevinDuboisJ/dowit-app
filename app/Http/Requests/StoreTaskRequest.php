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
            'taskType' => 'required|exists:task_types,id',
            'campus' => 'required|exists:campuses,id',
            'visit' => 'sometimes|array',
            'visit.id'  => [
                'sometimes',
                'required_with:visit',
                Rule::requiredIf(function () {
                    return in_array(request('task_type_id'), TaskTypeEnum::getPatientTransportIds());
                }),
                'numeric',
            ],
            'tags' => 'array',
            // 'assets' => 'array',
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
            ]
        ];

        if (isset($validated['tags'])) {
            $data['tags'] = $validated['tags'];
        }

        if (isset($validated['visit'])) {
            $data['visit'] = $validated['visit'];
        }

        return $data;
    }
}
