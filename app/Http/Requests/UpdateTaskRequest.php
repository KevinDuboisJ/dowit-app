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

    protected function prepareForValidation()
    {
        $this->merge([
            'updated_at' => Carbon::parse($this->updated_at)->setTimezone(config('app.timezone')),
        ]);
    }

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

            'help_requested' => [
                'boolean',
            ],

            'assignees' => [
                'array',
            ],

            'tags' => [
                'array',
            ],

            'comment' => [
                'string',
                'nullable',
            ],

            'updated_at' => [
                'required',
                'date',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $task = $this->route('task');

            if (!$task) {
                return;
            }

            $incomingAssignees = collect($this->input('assignees', []))
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $currentAssignees = $task->assignees()
                ->pluck('users.id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $incomingTags = collect($this->input('tags', []))
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $currentTags = $task->tags()
                ->pluck('tags.id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $incomingComment = trim(strip_tags($this->input('comment', '')));

            $hasChanges =
                $this->input('status') !== $task->status?->name ||
                $this->input('priority') !== $task->priority?->value ||
                (bool) $this->input('help_requested') !== (bool) $task->help_requested ||
                $incomingAssignees !== $currentAssignees ||
                $incomingTags !== $currentTags ||
                $incomingComment !== '';

            if (!$hasChanges) {
                $validator->errors()->add('form', 'Pas minstens één veld aan.');
            }
        });
    }

    public function prepareForDatabase()
    {
        $data = $this->validated();

        $requestKeys = array_keys($this->all());
        $validKeys = array_keys($this->rules());
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