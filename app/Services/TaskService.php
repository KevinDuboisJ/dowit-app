<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Enums\TaskStatus;
use App\Events\BroadcastEvent;
use Illuminate\Support\Facades\Cache;
use App\Enums\TaskStatus as TaskStatusEnum;

class TaskService
{
  private const DEFAULT_EXCLUDED_STATUSES = [
    TaskStatusEnum::Replaced->value,
    TaskStatusEnum::Completed->value,
    TaskStatusEnum::Skipped->value,
];

  public function updateTask(Task $task, array $data)
  {
    $clientTimestamp = Carbon::parse($data['beforeUpdateAt']);

    if ($clientTimestamp->lt($task->updated_at)) {
      return [
        'conflict' => true,
        'message' => 'Het bijwerken van de taak is mislukt omdat deze onlangs al is bijgewerkt',
        'latestData' => $task->load(Task::getRelationships()),
      ];
    }

    DB::transaction(function () use ($task, $data) {

      $task->fill(Arr::only($data, [
        'status_id',
        'priority',
        'needs_help',
        'updated_at',
      ]));

      $metadata = $this->trackMetadataChanges($task);
      $this->handleUserAssignments($task, $data, $metadata);
      $comment = $task->addComment($data['comment'] ?? '', $metadata);
      $this->handleAutoStatusReset($task, $data, $comment);
      $task->save();
    });

    $task->load(Task::getRelationships());
    Cache::put("task_{$task->id}", $task, now()->addMinutes(3));
    broadcast(new BroadcastEvent($task, 'task_updated', 'dashboard', $data));

    return [
      'success' => true,
      'task' => $task,
    ];
  }

  private function trackMetadataChanges(Task $task): array
  {
    $metadata = [];

    if ($task->isDirty('status_id')) {
      $metadata['changed_keys']['status'] = $task->status->name;
    }

    if ($task->isDirty('priority')) {
      $metadata['changed_keys']['priority'] = $task->priority;
    }

    if ($task->isDirty('needs_help')) {
      $metadata['changed_keys']['needs_help'] = $task->needs_help;
    }

    return $metadata;
  }

  private function handleUserAssignments(Task $task, array $data, array &$metadata): void
  {
    if (!empty($data['usersToAssign'])) {
      // Get IDs of already assigned users
      $alreadyAssignedIds = $task->assignees()->pluck('users.id')->toArray();

      // Filter only the new user IDs that are not already assigned
      $newUsersToAssign = array_diff($data['usersToAssign'], $alreadyAssignedIds);

      // Assign all users (if your assignUsers method handles duplicates gracefully)
      $task->assignUsers($data['usersToAssign']);

      // Only update metadata for truly new users
      if (!empty($newUsersToAssign)) {
        $metadata['changed_keys']['assignees'] = User::whereIn('id', $newUsersToAssign)
          ->pluck(DB::raw("CONCAT(firstname, ' ', lastname)"))
          ->toArray();
      }
    }

    if (!empty($data['usersToUnassign'])) {
      $task->unassignUsers($data['usersToUnassign']);
      $metadata['changed_keys']['unassignees'] = User::whereIn('id', $data['usersToUnassign'])
        ->pluck(DB::raw("CONCAT(firstname, ' ', lastname)"))
        ->toArray();
    }
  }

  private function handleAutoStatusReset(Task $task, array $data, Comment $comment): void
  {
    if ($task->assignees->isEmpty() && !empty($data['usersToUnassign'])) {
      $task->update(['status_id' => TaskStatus::Added->value]);

      $task->comments()->create([
        'user_id' => config('app.system_user_id'),
        'content' => 'Status werd automatisch omgezet naar Toegevoegd',
        'created_at' => $comment->created_at->addSecond(),
      ]);

      $task->load('comments');
    }
  }

  public function fetchAndCombineTasks($request)
  {

    $hasFilterByStatus = false;

    // Get common settings
    $relationships = Task::getRelationships();
    $filters = $request->filled('filters') ? $request->input('filters') : null;
    $sorters = $request->filled('sorters') ? $request->input('sorters') : null;

    // Get assigned tasks (non-paginated)
    $assignedTasks = Task::with($relationships)
      ->byAssigned()
      ->byActive()
      ->when($filters, function ($query) use ($filters, &$hasFilterByStatus) {
        $this->applyFilters($query, $filters, $hasFilterByStatus);
      })
      ->when(!$hasFilterByStatus, function ($query) {
        $query->whereNotIn('status_id', self::DEFAULT_EXCLUDED_STATUSES);
      })
      ->when($sorters, function ($query) use ($request) {
        foreach ($request->input('sorters', []) as $sorter) {
          if ($sorter['field'] === 'status.name') {
            // Add join for sorting by status name
            $query->leftJoin('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
              ->orderBy('task_statuses.name', $sorter['dir']);
          } elseif ($sorter['field'] === 'task_type.name') {
            // Add join for sorting by task type name
            $query->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
              ->orderBy('task_types.name', $sorter['dir']);
          } else {
            // Default sorting on the main table
            $query->orderBy($sorter['field'], $sorter['dir']);
          }
        }
      })
      ->orderBy('start_date_time', 'desc')
      ->select('tasks.*')
      ->get();

    // Get team tasks (paginated with joins for sorting)
    $teamTasks = Task::query()
      ->with($relationships)
      ->where(function ($query) {
        $query->byUserTeams()
          ->byNotAssigned()
          ->byActive();
      })
      ->when($filters, function ($query) use ($filters, &$hasFilterByStatus) {
        $this->applyFilters($query, $filters, $hasFilterByStatus);
      })
      ->when(!$hasFilterByStatus, function ($query) {
        $query->whereNotIn('status_id', self::DEFAULT_EXCLUDED_STATUSES);
      })
      ->when($sorters, function ($query) use ($request) {
        foreach ($request->input('sorters', []) as $sorter) {
          if ($sorter['field'] === 'status.name') {
            // Add join for sorting by status name
            $query->leftJoin('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
              ->orderBy('task_statuses.name', $sorter['dir']);
          } elseif ($sorter['field'] === 'task_type.name') {
            // Add join for sorting by task type name
            $query->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
              ->orderBy('task_types.name', $sorter['dir']);
          } else {
            // Default sorting on the main table
            $query->orderBy($sorter['field'], $sorter['dir']);
          }
        }
      })
      ->orderBy('start_date_time', 'desc')
      ->select('tasks.*') // Ensure only columns from `tasks` table are selected else ids of join wil also come in and confuse the eloquent model
      ->paginate($request->input('size', 1000));

    // Build pagination metadata
    $pagination = [
      'total' => $teamTasks->total(),
      'per_page' => $teamTasks->perPage(),
      'current_page' => $teamTasks->currentPage(),
      'last_page' => $teamTasks->lastPage(),
      'from' => $teamTasks->firstItem(),
      'to' => $teamTasks->lastItem(),
    ];

    // Merge assigned and team tasks
    $combinedTasks = $assignedTasks
      ->merge(collect($teamTasks->items())->values()) // Reset keys for team tasks
      ->unique('id'); // Avoid duplicates

    // Return results
    return [
      ...$pagination,
      'data' => $combinedTasks,
    ];
  }

  // Helper methods for better organization and reusability
  protected function applyFilters($query, $filters, &$hasFilterByStatus)
  {

    foreach ($filters as $filter) {
      $field = $filter['field'];
      $type = $filter['type'];
      $value = $filter['value'];

      if ($field === 'status_id' && $value) {
        $hasFilterByStatus = true;
        $taskStatusId = TaskStatusEnum::fromCaseName($value)->value;
        $query->where('status_id', $type, $taskStatusId);
      }

      if ($field === 'team_id' && $value) {
        $query->whereHas('teams', function ($teamQuery) use ($value) {
          $teamQuery->where('teams.id', $value);
        });
      }

      if ($field === 'assignedTo' && $value) {
        $query->whereHas(
          'assignees',
          fn($subQuery) =>
          $subQuery->where('firstname', $type, $value . '%')
            ->orWhere('lastname', $type, $value . '%')
        );
      }
    }
  }
}
