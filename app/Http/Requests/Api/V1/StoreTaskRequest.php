<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TaskPriorityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Override this method to modify input data before validation.
     */
    protected function prepareForValidation()
    {
        // Get the entire data payload or an empty array if not present.
        $data = $this->input('data', []);

        // Extract attributes, defaulting to an empty array.
        $attributes = $data['attributes'] ?? [];

        // Merge in the default status_id.
        $data['attributes'] = array_merge($attributes, [
            'status_id' => $attributes['status_id'] ?? 1,
        ]);

        // Merge the updated data back into the request.
        $this->merge(['data' => $data]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            // Ensure task_planner_id is not provided
            'data.attributes.task_planner_id' => 'prohibited',

            // Validate name and description as strings
            'data.attributes.name' => 'required|string',
            'data.attributes.description' => 'required|string',

            // Validate foreign keys:
            // campus_id should reference an existing campus
            'data.attributes.campus_id' => 'required|integer|exists:campuses,id',

            // task_type_id should reference an existing task type
            'data.attributes.task_type_id' => 'required|integer|exists:task_types,id',

            // space_id should be null or reference an existing space
            'data.attributes.space_id' => [
                'integer',
                Rule::exists('spaces.spaces', 'id'),
            ],

            // space_to_id should be null or reference an existing space
            'data.attributes.space_to_id' => [
                'nullable',
                'integer',
                Rule::exists('spaces.spaces', 'id'),
            ],

            // status_id should reference an existing task status
            'data.attributes.status_id' => 'required|integer|exists:task_statuses,id',

            // Validate priority to be one of the defined enum values
            'data.attributes.priority' => ['required', new Enum(TaskPriorityEnum::class)],

            // Validate teams as an array with at least one team
            'data.relationships.teams.data'   => 'required|array|min:1',
            'data.relationships.teams.data.*.id' => 'required|integer|exists:teams,id',

        ];

        return $rules;
    }
}
