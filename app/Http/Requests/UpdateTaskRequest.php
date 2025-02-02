<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Support\Carbon;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {

        $task = $this->route('task'); // Get the Task model from the route
        $user = $this->user(); // Get the authenticated user

        // If 'priority' is in the request, check if the user can update it
        // if ($this->has('priority')) {
        //     return $user->can('canUpdatePriority', $task);
        // }

        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation()
    {
        $this->prepareUsersToAssign();
        $this->prepareUsersToUnassign();

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
                'sometimes',
                'string',
                'in:' . implode(',', array_column(TaskStatus::cases(), 'name')),
            ],
            'priority' => [
                'sometimes',
                'string',
                'in:' . implode(',', array_column(TaskPriority::cases(), 'name')),
                'nullable',
            ],
            'needs_help' => [
                'sometimes',
                'bool',
            ],
            'usersToAssign' => [
                'sometimes',
                'array',
            ],
            'usersToUnassign' => [
                'sometimes',
                'array',
            ],
            'comment' => [
                'sometimes',
                'string',
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

    /**
     * Return validated data and exclude invalid fields.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Get all request keys
        $requestKeys = array_keys($this->all());

        // Get all valid keys from the rules
        $validKeys = array_keys($this->rules());

        // Check for any extra keys not in the rules
        $extraKeys = array_diff($requestKeys, $validKeys);

        if (!empty($extraKeys)) {
            abort(422, 'Invalid fields in request: ' . implode(', ', $extraKeys));
        }

        if (isset($validated['status'])) {

            $validated['status_id'] = TaskStatus::fromCaseName($validated['status'])->value;
            unset($validated['status']);
        }

        return $validated;
    }


    /**
     * Normalize 'usersToAssign' to extract user IDs.
     */
    protected function prepareUsersToAssign()
    {
        if ($this->has('usersToAssign')) {
            $userIds = array_map(
                fn($user) => is_array($user) && isset($user['value']) ? $user['value'] : $user,
                $this->usersToAssign
            );

            $this->merge([
                'usersToAssign' => $userIds,
            ]);
        }
    }

    /**
     * Normalize 'usersToUnassign' to extract user IDs.
     */
    protected function prepareUsersToUnassign()
    {
        if ($this->has('usersToUnassign')) {
            $userIds = array_map(
                fn($user) => is_array($user) && isset($user['value']) ? $user['value'] : $user,
                $this->usersToUnassign
            );

            $this->merge([
                'usersToUnassign' => $userIds,
            ]);
        }
    }
}
