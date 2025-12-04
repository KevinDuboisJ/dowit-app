<?php

namespace App\Console\Commands;

use App\Enums\TaskPlannerEvaluationResultEnum;
use Illuminate\Console\Command;
use App\Services\TaskPlannerService;
use Illuminate\Support\Facades\Log;
use App\Enums\TaskStatusEnum;

class HandleTaskPlanners extends Command
{
    protected $signature = 'tasks:handle-planners';
    protected $description = 'Create tasks defined by the active task planners';

    public function handle(TaskPlannerService $taskPlannerService)
    {
        try {
            // Retrieves today's task planners and returns only those scheduled to be triggered within the next hour
            $nextRunAtTaskPlanners = $taskPlannerService->getClosestTaskPlanners();

            if ($nextRunAtTaskPlanners->isNotEmpty()) {

                foreach ($nextRunAtTaskPlanners as $taskPlanner) {

                    $result = $taskPlannerService->applyExecutionRules($taskPlanner);

                    if ($result !== TaskPlannerEvaluationResultEnum::ShouldTrigger) {
                        continue;
                    }

                    $taskPlannerService->execute($taskPlanner, TaskStatusEnum::Added, $taskPlanner->getOffsetRunAt());

                    $taskPlannerService->reschedule($taskPlanner);
                }
            }
        } catch (\Throwable $e) {
            Log::debug([
                'message' => 'An error occurred while handling task planners: ' . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
