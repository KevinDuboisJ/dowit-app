<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaskPlannerService;
use Illuminate\Support\Facades\Log;
use App\Enums\TaskStatus;
use App\Events\BroadcastEvent;

class HandleTaskPlanners extends Command
{
    protected $signature = 'tasks:handle-planners';
    protected $description = 'Handle scheduled task planners';

    public function handle(TaskPlannerService $taskPlannerService)
    {
        try {
            // This handle tasks that have a start date time in the future
            $plannedTasks = $taskPlannerService->getPlannedTasksToActivate();

            if (!$plannedTasks->isEmpty()) {
                foreach ($plannedTasks as $task) {
                    $task->activate();
                    broadcast(new BroadcastEvent($task, 'task_created', 'TaskPlannerService'));
                }
            }

            // This gets closest taskplanners and cached it if not already done
            $nextRunAtTaskPlanners = $taskPlannerService->getClosestTaskPlanners();

            if ($nextRunAtTaskPlanners->isNotEmpty()) {

                $nextRunAt = $nextRunAtTaskPlanners->first()->next_run_at;

                // Objects are passed by reference when using foreach or passing them to a method.
                // This is why calling getTodayNextRunAtDatetime will retrieve the most up-to-date value and nextTaskPlannerRunAtDatetime can be null
                // This part of the code is used to handle frequentie logic.
                foreach ($nextRunAtTaskPlanners as $taskPlanner) {

                    // This handles cases where the next_run_at does not align with a specified frequency. e.g next_run_at = start_date_time is on monday but frequency is only for tuesdays
                    // Handle holiday dates by updating the task planners accordingly.
                    $taskPlannerService->handleHolidayDates($taskPlanner);

                    // // Handle specific days of the week
                    $taskPlannerService->handleSpecificDays($taskPlanner);

                    // // Handle each x day
                    // $taskPlannerService->handleEachXDay($taskPlanner);

                    if ($taskPlanner->next_run_at->equalTo($nextRunAt)) {

                        // Handle the action
                        $taskPlannerService->handleAction($taskPlanner);

                        // Trigger task
                        $taskPlannerService->triggerTask($taskPlanner, TaskStatus::Added, $nextRunAt);

                        // Update the next_due_date in recurrence
                        $taskPlanner->updateNextRunDate();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('HandleTaskPlanners exception: ' . $e->getMessage());
            Log::debug('Exception trace: ' . $e->getTraceAsString());
        }
    }
}
