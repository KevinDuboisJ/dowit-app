<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\TaskPlanner;
use App\Models\Task;
use App\Enums\TaskStatus;
use App\Models\Holiday;
use App\Enums\ApplyOnHoliday;
use App\Enums\TaskPlannerAction;
use App\Enums\TaskPlannerFrequency;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Services\TaskAssignmentService;
use App\Events\BroadcastEvent;
use Illuminate\Support\Facades\Auth;

class TaskPlannerService
{
  const CACHE_KEY = 'next_run_tasks';

  public function getPlannedTasksToActivate(): Collection
  {
    return Task::with(Task::getRelationships())->where('start_date_time', '<=', Carbon::now())
      ->where('is_active', false) // Adjust if 'is_inactive' is a boolean or equivalent column
      ->get();
  }

  public function getTodayTaskPlanners($nextRunAtDatetime = null): Collection
  {
    // Cache tasks for the day to avoid querying every minute
    // This will not refresh the cache if it already exists. Instead, it will return the cached value if it exists, or generate and store the new cache if it doesn't
    return Cache::remember(self::CACHE_KEY, $this->getCacheExpiration(), function () {
      return TaskPlanner::with(['teams', 'tags'])->where('next_run_at', '>=', Carbon::now()->second(0))
        ->where('next_run_at', '<=', Carbon::now()->endOfDay())
        ->where('is_active', true)
        ->orderBy('next_run_at')
        ->get();
    });
  }

  public function getClosestTaskPlanners()
  {
    // Fetch today's task planners
    $todayTaskPlanners = $this->getTodayTaskPlanners();

    // Ensure the collection is not empty
    if ($todayTaskPlanners->isEmpty()) {
      return collect(); // Return an empty collection if no task planners found
    }

    // // Sort task planners by next_run_at to ensure correct order
    // $sortedTaskPlanners = $todayTaskPlanners->sortBy('next_run_at');

    // Get the closest next_run_at value (the first task planner in the sorted list)
    $closestNextRunAt = $todayTaskPlanners->first()->next_run_at;

    // Filter all task planners with the closest next_run_at
    $closestTaskPlanners = $todayTaskPlanners->filter(function ($taskPlanner) use ($closestNextRunAt) {
      return $taskPlanner->next_run_at->eq($closestNextRunAt);
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

  public function triggerTask(TaskPlanner $taskPlanner, TaskStatus $status, Carbon|null $startDateTime = null)
  {
    try {
      DB::transaction(function () use ($taskPlanner, $status, $startDateTime) {
        // 1. Create the new task from recurrence
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
        ]);

        $task->save();

        // Add Initial comment
        // $task->comments()->create([
        //   'user_id' => $taskPlanner->created_by,
        //   'content' => $taskPlanner->comment
        // ]);

        // Handle assignations
        $this->handleAssignations($taskPlanner, $task);

        // Copy the planner's tags to the task
        $task->tags()->sync($taskPlanner->tags->pluck('id'));

        // Assign task to teams based on assignment rules
        TaskAssignmentService::assignTaskToTeams($task, $taskPlanner->teams->pluck('id')->toArray());

        broadcast(new BroadcastEvent($task, 'task_created', 'TaskPlannerService'));
      });
    } catch (Exception $e) {
      log::info('triggerTask exception: ' . $e->getMessage());
    }
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

  public function handleHolidayDates($taskPlanner)
  {
    $isHolidayToday = Holiday::whereDate('date', Carbon::now()->toDateString())->exists();

    if ($taskPlanner->on_holiday == ApplyOnHoliday::No && $isHolidayToday) {
      $this->triggerTask($taskPlanner, TaskStatus::Skipped);
      $taskPlanner->updateNextRunDate();
    } elseif ($taskPlanner->on_holiday == ApplyOnHoliday::OnlyOnHolidays && !$isHolidayToday) {
      $taskPlanner->updateNextRunDate();
    }
  }

  public function handleSpecificDays($taskPlanner)
  {
    if ($taskPlanner->frequency === TaskPlannerFrequency::SpecificDays) {
      if (!in_array($taskPlanner->next_run_at->format('l'), $taskPlanner->interval ?? [])) { // 'l' gives day name (e.g., 'Monday')
        $taskPlanner->updateNextRunDate();
      }
    }
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
