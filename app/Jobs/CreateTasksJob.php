<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\TaskPlanner;
use App\Models\Task;
use Illuminate\Support\Facades\Mail;
use Exception;
use App\Models\JobTracking;
use Illuminate\Support\Facades\Queue;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class CreateTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected TaskPlanner $taskPlanner;

    /**
     * Create a new job instance.
     */
    public function __construct(TaskPlanner $taskPlanner)
    {
        $this->taskPlanner = $taskPlanner;
    }

    public function uniqueId()
    {
        return $this->taskPlanner->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 1. Create the new task from recurrence
        $task = Task::create([
            'name' => $this->taskPlanner->name,
            // Any other task fields you need
        ]);

        // 2. Broadcast the task creation via WebSockets (TaskCreated event)
        //broadcast(new \App\Events\TaskCreated($task))->toOthers();

        // 3. Update the next_due_date in recurrence
        $nextRunDate = $this->taskPlanner->getNextRunDate(); // You can define this method in the model
        $this->taskPlanner->next_run_at = $nextRunDate->format('Y-m-d H:i:s');
        $this->taskPlanner->save();

        // 4. Schedule the next task creation job
        CreateTasksJob::dispatch($this->taskPlanner)->delay($nextRunDate);
    }

    public function failed(Exception $exception)
    {
        Mail::send([], [], function ($message) use ($exception) {
            $message->to('kevin.dubois@azmonica.be')
                ->subject('Taak creatie is mislukt')
                ->setBody("<p>{$exception->getMessage()}</p>", 'text/html');
        });
    }

    public function dispatchWithOverwrite(TaskPlanner $taskPlanner)
    {
        $modelId = $taskPlanner->id;
        // Step 1: Check if a job for the same model ID exists
        $existingJob = JobTracking::where('task_configuration_id', $modelId)->first();
        JobTracking::where('task_configuration_id', $modelId)->first();

        if ($existingJob) {
            // Step 2: If exists, delete the current job
            Queue::delete($existingJob->job_id);

            // Step 3: Remove tracking entry
            $existingJob->delete();
        }

        // Step 4: Dispatch the new job
        $job = self::dispatch($taskPlanner);
        dd($job);

        // Step 5: Track the newly dispatched job
        JobTracking::create([
            'model_id' => $modelId,
            'job_id' => $job->job->getJobId(),  // Store the job UUID
        ]);

        return $job;
    }
}
// $taskConfigs = Cache::get('task-configurations');

// if (!$taskConfigs) {
//     $taskConfigs = TaskPlanner::where('next_run_at', '<=', now())->get();
//     Cache::put('task-configurations', $taskConfigs, 60); // Cache for 60 minutes
// }

// foreach ($taskConfigs as $config) {
//     // Create a unique key for each task configuration based on its ID
//     $lockKey = 'task-generation-lock-' . $config->id;

//     // Attempt to acquire a lock for the task creation
//     $lock = Cache::lock($lockKey, 300); // Lock for 5 minutes (adjust as necessary)

//     if ($lock->get()) {
//         // Dispatch the job to create the task
//         CreateTasksJob::dispatch($config);

//         // The lock will be automatically released when the job finishes,
//         // or you can manually release the lock with $lock->release() if necessary.
//     } else {
//         // If lock exists, skip dispatching to avoid duplicates
//         // Optionally log or handle the skipped task generation
//         continue;
//     }
// }