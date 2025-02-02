<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use Illuminate\Console\Command;
use App\Models\TaskPlanner;
use Illuminate\Support\Facades\Log;
use App\Services\TaskPlannerService;

class FailoverTaskPlanners extends Command
{
    protected $signature = 'failover:tasks-handle';
    protected $description = 'Handle missed tasks';

    public function handle(TaskPlannerService $TaskPlannerService)
    {
        try {
            $missedTasks = TaskPlanner::where('next_run_at', '<=', now())->where('is_active', true)->get();
            foreach ($missedTasks as $taskPlanner) {

                // Handle the action
                $TaskPlannerService->handleAction($taskPlanner);

                // Trigger the missed task
                $TaskPlannerService->triggerTask($taskPlanner, TaskStatus::Added, $taskPlanner->next_run_at);
                
                // Update the next_due_date in recurrence
                $taskPlanner->updateNextRunDate();
            }
        } catch (\Exception $e) {
            Log::info('HandleMissedTasks exception: ' . $e->getMessage());
        }
    }
}
