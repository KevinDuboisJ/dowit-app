<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskPriorityEnum;
use App\Enums\TaskStatusEnum;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert to app timezone as all javascript datetime function uses UTC(0)
        $this->merge([
            'updated_at' => Carbon::parse($this->updated_at)->setTimezone(config('app.timezone')),
            //'beforeUpdateAt' => Carbon::parse($this->beforeUpdateAt)->setTimezone(config('app.timezone')),
        ]);
    }

    /**
     * Define validation rules.
     */

    public function rules(): array
    {
        return [
            'status' => [
                'string',
                'in:' . implode(',', array_column(TaskStatusEnum::cases(), 'name')),
            ],

            'priority' => [
                'string',
                new Enum(TaskPriorityEnum::class),
                'nullable',
            ],

            'needs_help' => [
                'bool',
            ],

            'assignees' => 'array',

            'comment' => [
                'string',
                'nullable'
            ],

            'updated_at' => [
                'required',
                'date',
            ],

            'beforeUpdateAt' => [
                'required',
                'date',
            ],
        ];
    }

    public function prepareForDatabase()
    {
        $data = $this->validated();

        // Get all request keys
        $requestKeys = array_keys($this->all());

        // Get all valid keys from the rules
        $validKeys = array_keys($this->rules());

        // Check for any extra keys not in the rules
        $extraKeys = array_diff($requestKeys, $validKeys);

        if (!empty($extraKeys)) {
            abort(422, 'Invalid fields in request: ' . implode(', ', $extraKeys));
        }

        if (isset($data['status'])) {
            $data['status_id'] = TaskStatusEnum::fromCaseName($data['status'])->value;
            unset($data['status']);
        }

        return $data;
    }
}
