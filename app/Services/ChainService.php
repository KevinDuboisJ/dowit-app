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
    public static function execute(Chain $chain, $context): void
    {
        if (! $chain->is_active) {
            return;
        }

        if (! self::matchesTrigger($chain, $context)) {
            return;
        }

        // trigger_conditions has not been implemented in the UI yet.
        if (! self::matches($chain->trigger_conditions ?? [], $context)) {
            return;
        }

        foreach ($chain->actions ?? [] as $actionKey => $actionParams) {
            switch ($actionKey) {
                case ChainActionType::CreateTask->name:
                    $payload = self::buildCreateTaskPayload($actionParams, $context);
                    self::handleCreateTask($chain, $context, $payload);
                    break;

                case ChainActionType::CustomCode->name:
                    self::executeScript($chain, $context, $actionParams);
                    break;

                default:
                    Log::warning("Unknown chain action: {$actionKey}", [
                        'chain_id' => $chain->id,
                    ]);
                    break;
            }
        }
    }

    public static function executeForCompletedTask(Task $task): void
    {
        $chains = Chain::query()
            ->where('is_active', true)
            ->where('trigger_type', 'task_completed')
            ->where('trigger_task_type_id', $task->task_type_id)
            ->get();

        foreach ($chains as $chain) {
            self::execute($chain, $task);
        }
    }

    public static function executeForApi(array $context = []): void
    {
        $chains = Chain::query()
            ->where('is_active', true)
            ->where('trigger_type', 'api')
            ->get();

        foreach ($chains as $chain) {
            self::execute($chain, $context);
        }
    }

    protected static function matchesTrigger(Chain $chain, $context): bool
    {
        return match ($chain->trigger_type) {
            'task_completed' => $context instanceof Task
                && (int) $chain->trigger_task_type_id === (int) $context->task_type_id,

            'api' => is_array($context) || is_object($context),

            default => false,
        };
    }

    protected static function handleCreateTask(Chain $chain, $context, array $params): void
    {
        $task = Task::create([
            'name' => Arr::get($params, 'name'),
            'start_date_time' => Carbon::now(),
            'description' => tiptap_converter()->asHTML(Arr::get($params, 'description') ?? ''),
            'task_type_id' => Arr::get($params, 'task_type_id'),
            'campus_id' => Arr::get($params, 'campus_id'),
            'space_id' => Arr::get($params, 'space_id'),
            'space_to_id' => Arr::get($params, 'space_to_id'),
            'status_id' => 1,
            'locker_id' => Arr::get($params, 'locker_id'),
        ]);

        $teamIds = $chain->teams->pluck('id')->toArray();
        $task->teams()->sync($teamIds);

        broadcast(new BroadcastEvent($task, 'task_created', 'ChainService'));
    }

    protected static function executeScript(Chain $chain, $context, array $params): void
    {
        $className = Arr::get($params, ChainActionType::CustomCode->name);

        if (! $className) {
            logger()->warning('Missing custom code class', [
                'chain_id' => $chain->id,
            ]);

            return;
        }

        $actionClass = $className;

        $action = rescue(
            fn() => app($actionClass),
            function (\Throwable $e) use ($chain, $actionClass) {
                logger()->error('Failed to resolve custom code action', [
                    'chain_id' => $chain->id,
                    'action_class' => $actionClass,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        );

        if (! $action || ! method_exists($action, 'executeChainAction')) {
            logger()->error('Invalid custom code action', [
                'chain_id' => $chain->id,
                'action_class' => $actionClass,
            ]);

            return;
        }

        $action->executeChainAction($context, $chain);
    }

    private static function buildCreateTaskPayload(array $params, mixed $context): array
    {
        $payload = [];
        $inheritMissing = (bool) ($params['inherit_missing_from_trigger_task'] ?? false);

        foreach ($params as $field => $value) {
            $payload[$field] = self::resolveValue($value, $field, $context, $inheritMissing);
        }

        // Add the locker id from the previous task
        if ($context->locker_id) {
            $payload['locker_id'] = $context->locker_id;
        };

        return $payload;
    }

    private static function resolveValue(mixed $value, string $field, mixed $context, bool $inheritMissing): mixed
    {
        // if ($value !== self::KEEP_ORIGINAL) {
        //     return $value;
        // }

        if (!$inheritMissing || $value !== null) {
            return $value;
        }

        if (! $context instanceof Task) {
            return null;
        }

        return match ($field) {
            'name' => $context->name,
            'description' => $context->description,
            'task_type_id' => $context->task_type_id,
            'campus_id' => $context->campus_id,
            'space_id' => $context->space_id,
            'space_to_id' => $context->space_to_id,
            'locker_id' => $context->locker_id,
            default => null,
        };
    }

    protected static function matches(array $conditions, $context): bool
    {
        foreach ($conditions as $field => $value) {
            $actual = $context instanceof Task
                ? data_get($context, $field)
                : Arr::get((array) $context, $field);

            if ($actual != $value) {
                return false;
            }
        }

        return true;
    }
}
