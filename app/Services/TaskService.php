<?php

namespace App\Services;

use App\Enums\TaskPriorityEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Events\BroadcastEvent;
use Illuminate\Support\Facades\Cache;
use App\Enums\TaskStatusEnum;

class TaskService
{
  public function create(array $data): Task
  {
    try {

      $userId =  Auth::id() ?? config('app.system_user_id');
      $usesPatientlist = isset($data['visit']);

      DB::connection('mysql')->beginTransaction();
      if ($usesPatientlist) {
        DB::connection('patientlist')->beginTransaction();
      }

      // build payload
      $taskPayload = $data['task'];

      if ($usesPatientlist) {
        $taskPayload['visit_id'] = $data['visit']['id'];
      }

      // Create task
      $task = Task::create($taskPayload);

      //Sync side stuff (tags, assignees, teams)
      $task->syncTags($data['tags']);
      $task->syncAssignees($data['assignees']);
      $task->syncTeams($data['teamsMatchingAssignment']);

      // Task created comment
      $task->comments()->create([
        'created_by' => $userId,
        'content' => 'Taak aangemaakt',
      ]);

      // commit
      DB::connection('mysql')->commit();

      if ($usesPatientlist) {
        DB::connection('patientlist')->commit();
      }

      // eager
      return $task->load(Task::getRelationships());
    } catch (\Throwable $e) {

      DB::connection('mysql')->rollBack();
      if ($usesPatientlist) {
        DB::connection('patientlist')->rollBack();
      }
      throw $e;
    }
  }

  public function updateTask(Task $task, array $data)
  {
    // Compare the task's updated_at from the client with the database value. To detect whether the task was already modified.
    if ($data['updated_at']->lt($task->updated_at)) {
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

      $task->comments()->create([
        'status_id' => $task->isDirty('status_id') ? $task->status_id : null,
        'needs_help' => $task->isDirty('needs_help') ? $task->needs_help : null,
        'content' => $data['comment'] ?? '',
        'metadata' => $this->trackTaskMetaDataChanges($task, $data),
      ]);

      // Only sync assignees if the client actually sent them
      if (array_key_exists('assignees', $data)) {
        $task->syncAssignees($data['assignees']);
      }

      $this->handleAutoStatusReset($task, $data);
      $task->save();
    });

    PatientService::handleFinalCleanTask($task);

    $task->load(Task::getRelationships());
    Cache::put("task_{$task->id}", $task, now()->addMinutes(3));
    broadcast(new BroadcastEvent($task, 'task_updated', 'dashboard', $data));

    return [
      'success' => true,
      'task' => $task,
    ];
  }

  private function handleAutoStatusReset(Task $task, array $data): void
  {
    // If there are no assignees after unassignment, reset status to 'Added'
    if ($task->assignees->isEmpty() && !empty($data['usersToUnassign'])) {
      $task->update(['status_id' => TaskStatusEnum::Added->value]);
      $task->comments()->create([
        'created_by' => config('app.system_user_id'),
        'content' => 'Status werd automatisch omgezet naar Toegevoegd',
      ]);
    }
  }

  public function trackTaskMetaDataChanges(Task $task, array $data): ?array
  {
    $changed = [];

    // Current assignees from DB
    $currentAssignees = $task->assignees()->pluck('users.id')->all();

    // Status
    if ($task->isDirty('status_id')) {
      $changed['status'] = $task->status->name;
    }

    // Priority (guard against missing key for partial updates)
    if ($task->isDirty('priority') && array_key_exists('priority', $data)) {
      $priorityEnum = TaskPriorityEnum::tryFrom($data['priority']);
      $changed['priority'] = $priorityEnum?->name ?? $data['priority'];
    }

    // Needs help
    if ($task->isDirty('needs_help')) {
      $changed['needs_help'] = $task->needs_help;
    }

    // Assignees (only if the client actually sent them)
    if (array_key_exists('assignees', $data)) {
      $newAssignees = $data['assignees'] ?? [];

      $assigned   = array_values(array_diff($newAssignees, $currentAssignees));
      $unassigned = array_values(array_diff($currentAssignees, $newAssignees));

      if (!empty($assigned) || !empty($unassigned)) {

        $allChangedIds = array_unique([...$assigned, ...$unassigned]);

        // One query for all changed users
        $namesById = User::whereIn('id', $allChangedIds)
          ->pluck(DB::raw("CONCAT(firstname, ' ', lastname)"), 'id');

        if (!empty($assigned)) {
          $changed['assignees'] = array_values(
            $namesById->only($assigned)->all()
          );
        }

        if (!empty($unassigned)) {
          $changed['unassignees'] = array_values(
            $namesById->only($unassigned)->all()
          );
        }
      }
    }

    if (empty($changed)) {
      return null;
    }

    return [
      'changed_keys' => $changed,
    ];
  }

  public function fetchAndCombineTasks($request)
  {
    $hasFilterByStatus = false;

    // Get common settings
    $relationships = [
      'visit' => fn($query) => $query->with(['patient', 'bed.room']),
      'tags',
      'status',
      'taskType' => fn($query) => $query->with(['assets']),
      'space',
      'spaceTo',
      'assignees',
      'teams' => fn($query) => $query->select('teams.id', 'teams.name'),
    ];

    $filters = $request->filled('filters') ? $request->input('filters') : null;
    $sorters = $request->filled('sorters') ? $request->input('sorters') : null;

    // Get tasks
    $tasks = Task::with($relationships)
      ->when($filters, function ($query) use ($filters, &$hasFilterByStatus) {
        $this->applyFilters($query, $filters, $hasFilterByStatus);
      })
      ->when(!$hasFilterByStatus, function ($query) {
        $query->byActive();
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
      // nulls last equivalent for DESC: COALESCE(null, 0) keeps nulls at the bottom
      ->orderByDesc(DB::raw('COALESCE(tasks.needs_help, 0)'))
      // "task_type_id = 5 first" -> 0 first, then others
      ->orderBy(DB::raw('tasks.task_type_id != 5')) // ASC by default
      // Custom order for status_id: (1, 2) first; keep FIELD if MySQL/MariaDB
      ->orderByRaw('FIELD(status_id, ?, ?) DESC', [1, 2])
      ->orderByDesc('start_date_time')
      ->select('tasks.*')
      ->paginate($request->input('size', 1000));

    // Build pagination metadata
    $pagination = [
      'total' => $tasks->total(),
      'per_page' => $tasks->perPage(),
      'current_page' => $tasks->currentPage(),
      'last_page' => $tasks->lastPage(),
      'from' => $tasks->firstItem(),
      'to' => $tasks->lastItem(),
    ];

    // Return results
    return [
      ...$pagination,
      'data' => collect($tasks->items())->values(),
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