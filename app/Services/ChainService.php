<?php

namespace App\Services;

use App\Enums\ChainActionType;
use App\Models\Chain;
use App\Models\Task;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Events\BroadcastEvent;
use Carbon\Carbon;

class ChainService
{
    /**
     * Run all active chains for this trigger.
     *
     * @param  'internal'|'api'  $triggerType
     * @param  mixed             $context     Task model | Request
     */
    public static function execute(Chain $chain, $context)
    {
        // 1) Check if the chain matches its trigger conditions
        if (!self::matches($chain->trigger_conditions ?? [], $context)) {
            // trigger_conditions has not been implemented
            return;
        }

        // 2) Loop through the `actions` array and process each one
        foreach ($chain->actions ?? [] as $actionKey => $actionParams) {
            match ($actionKey) {
                ChainActionType::CreateTask->name => self::handleCreateTask($chain, $actionParams),
                ChainActionType::CustomCode->name => self::handleCustomCode($chain, $context, $actionParams),
                default => Log::warning("Unknown chain action: {$actionKey}")
            };
        }
    }

    protected static function handleCreateTask(Chain $chain, array $params): void
    {
        $task = Task::create([
            'name'              => Arr::get($params, 'name'),
            'start_date_time'   => Carbon::now(),
            'description'       => tiptap_converter()->asHTML(Arr::get($params, 'description') ?? ''),
            'task_type_id'      => Arr::get($params, 'task_type_id'),
            'campus_id'         => Arr::get($params, 'campus_id'),
            'space_id'          => Arr::get($params, 'space_id'),
            'status_id'         => 1,
        ]);

        // Get team IDs from the chain relationship
        $teamIds = $chain->teams->pluck('id')->toArray();

        $task->teams()->sync($teamIds);

        broadcast(new BroadcastEvent($task, 'task_created', 'ChainService'));
    }

    protected static function handleCustomCode(Chain $chain, $context, array $params)
    {
        $actionClass = 'App\\Services\\' . $chain->actions[ChainActionType::CustomCode->name][ChainActionType::CustomCode->name];
        $action = rescue(fn() => app($actionClass), function (\Throwable $e) use ($chain) {
            logger()->error('Failed to resolve custom code action', [
                'chain_id' => $chain->id,
                'error' => $e->getMessage(),
            ]);
            return;
        });

        $action->handle($context, $chain);
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
