<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\TaskPlanner;
use App\Models\Task;
use App\Enums\TaskStatus;
use App\Models\Holiday;
use App\Enums\ApplyOnHoliday;
use App\Enums\TaskPlannerAction;
use App\Enums\TaskPlannerEvaluationResultEnum;
use App\Enums\TaskPlannerFrequency;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Services\TaskAssignmentService;
use App\Events\BroadcastEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Mail\Message;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;

class TaskPlannerService
{
  const CACHE_KEY = 'next_run_tasks';

  public function getScheduledTasks(): Collection
  {
    return Task::with(Task::getRelationships())->where('start_date_time', '<=', Carbon::now())
      ->where('status_id', TaskStatus::Scheduled)
      ->get();
  }

  public function getTodayTaskPlanners(): Collection
  {
    $now = Carbon::now()->second(0);
    $end = Carbon::now()->endOfDay();
    $yesterday = $now->copy()->subDay();

    // Cache tasks for the day to avoid querying every minute
    // This will not refresh the cache if it already exists. Instead, it will return the cached value if it exists, or generate and store the new cache if it doesn"t
    return Cache::remember(self::CACHE_KEY, $this->getCacheExpiration(), function () use ($now, $end, $yesterday) {
      return TaskPlanner::with(['teams', 'tags', 'visit', 'taskType'])
        ->select('task_planners.*') // Select only the taskplanners columns and no the task_types
        ->join('task_types', 'task_planners.task_type_id', '=', 'task_types.id')  // Join so we can reference task_types.creation_time_offset in byWithinWindow
        ->where(function (Builder $query) use ($now, $end, $yesterday) {
          $query->byWithinWindow($now, $end)
            ->orWhereBetween('next_run_at', [$yesterday, $now]);
        })
        ->where('is_active', true)
        ->orderBy('next_run_at')
        ->get();
    });
  }

  public function getClosestTaskPlanners(): Collection
  {
    // Retrieves today's task planners and returns only those scheduled to be triggered within the next hour
    $todayTaskPlanners = $this->getTodayTaskPlanners();
    $now = Carbon::now()->second(0);
    $oneMinuteLater = now()->addMinute();

    $closestTaskPlanners = $todayTaskPlanners->filter(function ($taskPlanner) use ($now, $oneMinuteLater) {

      $runAt = $taskPlanner->getOffsetRunAt();
      if (! $runAt) {
        return false;
      }

      // Return true if it’s within [ now and oneHourLater ] OR already past
      return $runAt->lessThanOrEqualTo($now, $oneMinuteLater);
    });
    return $closestTaskPlanners;
  }

  public function getTaskPlannersByNextRunAt($nextRunAtDatetime): Collection
  {
    // Attempt to get the cached tasks
    $todayTaskPlanners = Cache::get(self::CACHE_KEY);

    // If cache does not exist, trigger the caching method
    if ($todayTaskPlanners === null) {
      $todayTaskPlanners = $this->getTodayTaskPlanners();
    }

    return $todayTaskPlanners->where('next_run_at', $nextRunAtDatetime);
  }

  public function getTodayNextRunAtDatetime(): ?Carbon
  {
    // Fetch today's task planners once
    $todayTaskPlanners = $this->getTodayTaskPlanners();

    // Check if the collection is not empty and get the first item,  
    $firstTaskPlanner = $todayTaskPlanners->first();

    // Return the parsed next_run_at if the first task planner exists, otherwise return null
    return $firstTaskPlanner ? $firstTaskPlanner->next_run_at : null;
  }

  protected function creationTimeOffSet(TaskPlanner $taskPlanner, Task $task)
  {
    $offset = -abs($taskPlanner->taskType->creation_time_offset);

    if ($offset) {
      $task->start_date_time = $task->start_date_time->addMinutes($offset);
    }
  }

  public function createTask(TaskPlanner $taskPlanner, TaskStatus $status, Carbon|null $startDateTime = null)
  {
    try {
      DB::transaction(function () use ($taskPlanner, $status, $startDateTime) {

        // Create the new task from taskplanner
        $task = new Task([
          'task_planner_id' => $taskPlanner->id,
          'description' => $taskPlanner->description,
          'start_date_time' => $startDateTime ?? $taskPlanner->next_run_at,
          'name' => $taskPlanner->name,
          'campus_id' => $taskPlanner->campus_id,
          'task_type_id' => $taskPlanner->task_type_id,
          'space_id' => $taskPlanner->space_id,
          'space_to_id' => $taskPlanner->space_to_id,
          'status_id' => $status->value,
          'visit_id' => $taskPlanner->visit_id,
        ]);

        $task->save();

        // Handle assignations
        $this->handleAssignations($taskPlanner, $task);

        // Copy the planner's tags to the task
        $task->tags()->sync($taskPlanner->tags->pluck('id'));

        // Assign task to teams based on assignment rules
        TaskAssignmentService::assignTaskToTeams($task, $taskPlanner->teams->pluck('id')->toArray());

        broadcast(new BroadcastEvent($task, 'task_created', 'TaskPlannerService'));
      });
    } catch (\Throwable $e) {
      Log::debug([
        'message' => 'An error occurred in createTask: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
      ]);
      throw $e; // Rethrow so outer catch can also catch it
    }
  }

  public function deactivate($taskPlanner): TaskPlannerEvaluationResultEnum
  {
    $taskPlanner->deactivate();
    return TaskPlannerEvaluationResultEnum::Deactivated;
  }

  public function reschedule($taskPlanner): TaskPlannerEvaluationResultEnum
  {
    $taskPlanner->updateNextRunDate();
    return TaskPlannerEvaluationResultEnum::Rescheduled;
  }
  //  public function toMail($notifiable)
  //   {
  //       return (new MailMessage)
  //                   ->subject('Herziening van Ultimo-ruimte nodig')
  //                   ->line("De ruimte $this->spaceName moet worden herzien vanwege ontbrekende gegevens.")
  //                   ->action('Ultimo', url('https://ultimoweb.monica.be'));
  //   }
  public function applyExecutionRules(TaskPlanner $taskPlanner): TaskPlannerEvaluationResultEnum
  {
    if ($taskPlanner->visit_id && $taskPlanner->visit?->bed_id === null && $taskPlanner->visit?->discharged_at === null) {
      Mail::html('<p>Patiënt met opnamenummer: ' . $taskPlanner->visit->number . ' heeft geen bed en is nog niet ontslagen</p>', function (Message $message) {
        $message->to('kevin.dubois@azmonica.be')
          ->subject('Dowit - Patiënt in taakplanner zonder bed');
      });
    }

    if ($taskPlanner->visit_id && $taskPlanner->visit?->discharged_at !== null) {
      return $this->deactivate($taskPlanner);
    }

    if ($taskPlanner->nextRunAtIsExcluded() || $this->handleHoliday($taskPlanner)) {
      return $this->reschedule($taskPlanner);
    }

    $this->handleAction($taskPlanner);

    return TaskPlannerEvaluationResultEnum::ShouldTrigger;
  }

  public function handleAction(TaskPlanner $taskPlanner)
  {
    switch ($taskPlanner->action) {
      case TaskPlannerAction::Replace:
        $latestTask = $taskPlanner->tasks()
          ->whereNotIn('status_id', [
            TaskStatus::Completed->value,
            TaskStatus::Replaced->value
          ])
          ->latest()
          ->first();

        if ($latestTask) {
          $latestTask->status_id = TaskStatus::Replaced->value;
          $latestTask->addComment('Taak is vervangen');
          $latestTask->save();
        }
        break;
    }
  }

  public function handleHoliday($taskPlanner): bool
  {
    $isHoliday = Holiday::whereDate('date', $taskPlanner->next_run_at->toDateString())->exists();

    if ($taskPlanner->on_holiday == ApplyOnHoliday::No && $isHoliday) {
      $this->createTask($taskPlanner, TaskStatus::Skipped);
      return true;
    }

    if ($taskPlanner->on_holiday == ApplyOnHoliday::OnlyOnHolidays && !$isHoliday) {
      return true;
    }

    return false;
  }

  public static function getCacheKey(): string
  {
    return self::CACHE_KEY;
  }

  public function getCacheExpiration()
  {
    // Retrieve the next taskPlanner and determine how long to cache until it runs
    $nextTask = TaskPlanner::where('next_run_at', '>=', Carbon::now())
      ->orderBy('next_run_at')
      ->first();

    if ($nextTask) {
      // Cache until the next taskPlanner's due time
      return Carbon::now()->diffInSeconds($nextTask->next_run_at);
    }

    // If no taskPlanner, cache for a long period (e.g., 1 hour)
    return 3600;
  }

  public function handleAssignations($taskPlanner, $task)
  {
    // User assignations
    $users = $taskPlanner->assignments['users'] ?? null;

    if ($users) {

      $oneTimeOcurrence = $taskPlanner->assignments['one_time_recurrence'] ?? null;
      $task->assignees()->attach($users);

      if ($oneTimeOcurrence) {
        $taskPlanner->assignments = null;
        $taskPlanner->save();
      }
    }
  }

  /**
   * Sync the TaskPlanner’s teams from a pre-computed array of IDs.
   *
   * @param  TaskPlanner  $planner
   * @param  int[]        $teamIds
   */
  public function syncAssignedTeams(TaskPlanner $planner, array $teamIds): void
  {
    // Only sync the IDs the user could actually assign
    $allowed = Auth::user()->teams->pluck('id')->all();
    $toSync  = array_intersect($teamIds, $allowed);

    $planner->teams()->sync($toSync);
  }
}
