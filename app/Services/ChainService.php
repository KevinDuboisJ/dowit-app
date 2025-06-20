<?php

namespace App\Services;

use App\Models\Chain;
use App\Models\Task;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class ChainService
{
    /**
     * Run all active chains for this trigger.
     *
     * @param  'internal'|'api'  $triggerType
     * @param  mixed             $context     Task model or array
     */
    public static function execute(Chain $chain, $context): void
    {
        // 1) Custom-code chains get the entire context to do whatever they please
        if ($chain->action_type === 'custom_code' && $chain->custom_code_class) {
            $action = app($chain->custom_code_class);
            $action->handle($context, $chain);
            return;
        }

        // 2) Otherwise, this is a "create_task" chain: apply db‐defined conditions…
        if (! self::matches($chain->trigger_conditions, $context)) {
            return;
        }

        // …and create the follow-up task
        $params = $chain->action_params ?? [];
        Task::create([
            'task_type'  => Arr::get($params, 'task_type', 'followup'),
            'title'      => Arr::get($params, 'title', 'Automated follow-up'),
            'due_date'   => now()->addMinutes(Arr::get($params, 'due_in_minutes', 60)),
            'related_id' => $context instanceof Task ? $context->id : null,
            // …etc, map any other params…
        ]);
    }

    protected static function matches(array $conditions, $context): bool
    {
        foreach ($conditions as $field => $value) {
            $actual = $context instanceof Task
                ? data_get($context, $field)
                : Arr::get($context, $field);

            if ($actual != $value) {
                return false;
            }
        }
        return true;
    }
}
