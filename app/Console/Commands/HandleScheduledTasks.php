<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaskPlannerService;
use Illuminate\Support\Facades\Log;
use App\Events\BroadcastEvent;

class HandleScheduledTasks extends Command
{
    protected $signature = 'tasks:handle-scheduled_tasks';
    protected $description = 'Change scheduled tasks status to "Added"';

    public function handle(TaskPlannerService $taskPlannerService)
    {
        try {
            // Get tasks that have a start date time in the past
            $plannedTasks = $taskPlannerService->getScheduledTasks();

            if (!$plannedTasks->isEmpty()) {
                foreach ($plannedTasks as $task) {
                    $task->activate();
                    broadcast(new BroadcastEvent($task, 'task_created', 'TaskPlannerService'));
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
