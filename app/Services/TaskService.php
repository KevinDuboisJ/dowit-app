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
use App\Models\Tag;
use App\Models\TaskStatus;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class TaskService
{
  public function create(array $data): Task
  {
    try {

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

      // Force identical timestamps using now() to identify the task creation comment
      $now = now();

      $task = Task::create([
        ...$taskPayload,
        'created_at' => $now,
        'updated_at' => $now,
      ]);

      //Sync side stuff (tags, assignees, teams)
      $task->syncTags($data['tags']);
      $task->syncAssignees($data['assignees']);
      $task->syncTeams($data['teamsMatchingAssignment']);

      $userId =  Auth::id() ?? config('app.system_user_id');

      // Task created comment
      $task->comments()->create([
        'created_by' => $userId,
        'created_at' => $now,
        'updated_at' => $now,
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
        'help_requested',
        'updated_at',
      ]));

      $task->comments()->create([
        'status_id' => $task->isDirty('status_id') ? $task->status_id : null,
        'help_requested' => $task->isDirty('help_requested') ? $task->help_requested : null,
        'content' => $data['comment'] ?? '',
        'metadata' => $this->trackTaskMetaDataChanges($task, $data),
      ]);

      // Only sync if the client actually sent them
      if (array_key_exists('assignees', $data)) {
        $task->syncAssignees($data['assignees']);
      }

      if (array_key_exists('tags', $data)) {
        $task->syncTags($data['tags']);
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
        'status_id' => TaskStatusEnum::Added->value,
      ]);
    }
  }

  public function trackTaskMetaDataChanges(Task $task, array $data): ?array
  {
    $changes = [];

    // Status
    if ($task->isDirty('status_id')) {
      $oldStatus = TaskStatusEnum::tryFrom((int) $task->getOriginal('status_id'));
      $newStatus = TaskStatusEnum::tryFrom((int) $task->status_id);

      $changes['status'] = [
        'from' => $oldStatus
          ? [
            'id' => $oldStatus->value,
            'value' => $oldStatus->name,
          ]
          : null,
        'to' => $newStatus
          ? [
            'id' => $newStatus->value,
            'value' => $newStatus->name,
          ]
          : null,
      ];
    }

    // Priority
    if ($task->isDirty('priority') && array_key_exists('priority', $data)) {
      $oldPriority = $task->getOriginal('priority');
      $newPriority = $task->priority;

      $changes['priority'] = [
        'from' => $oldPriority
          ? [
            'id' => $oldPriority->value,
            'value' => $oldPriority->name
          ]
          : null,
        'to' => $newPriority
          ? [
            'id' => $newPriority->value,
            'value' => $newPriority->name,
          ]
          : null,
      ];
    }

    // Needs help
    if ($task->isDirty('help_requested')) {
      $changes['help_requested'] = [
        'from' => [
          'value' => (bool) $task->getOriginal('help_requested'),
        ],
        'to' => [
          'value' => (bool) $task->help_requested,
        ],
      ];
    }

    // Assignees
    if (array_key_exists('assignees', $data)) {
      $currentAssignees = $task->assignees()
        ->pluck('users.id')
        ->map(fn($id) => (int) $id)
        ->all();

      $newAssignees = collect($data['assignees'] ?? [])
        ->map(fn($id) => (int) $id)
        ->all();

      $added = array_values(array_diff($newAssignees, $currentAssignees));
      $removed = array_values(array_diff($currentAssignees, $newAssignees));

      if (!empty($added) || !empty($removed)) {
        $allChangedIds = array_values(array_unique([...$added, ...$removed]));

        $usersById = User::whereIn('id', $allChangedIds)
          ->get()
          ->keyBy('id');

        $mapUser = fn(int $id) => [
          'id' => $id,
          'value' => trim(
            (($usersById[$id]->firstname ?? '') . ' ' . ($usersById[$id]->lastname ?? ''))
          ) ?: null,
        ];

        $changes['assignees'] = [
          'added' => array_map($mapUser, $added),
          'removed' => array_map($mapUser, $removed),
        ];
      }
    }

    // Tags
    if (array_key_exists('tags', $data)) {
      $currentTags = $task->tags()
        ->pluck('tags.id')
        ->map(fn($id) => (int) $id)
        ->all();

      $newTags = collect($data['tags'] ?? [])
        ->map(fn($id) => (int) $id)
        ->all();

      $added = array_values(array_diff($newTags, $currentTags));
      $removed = array_values(array_diff($currentTags, $newTags));

      if (!empty($added) || !empty($removed)) {
        $allChangedIds = array_values(array_unique([...$added, ...$removed]));

        $tagsById = Tag::whereIn('id', $allChangedIds)
          ->get()
          ->keyBy('id');

        $mapTag = fn(int $id) => [
          'id' => $id,
          'value' => $tagsById[$id]->name ?? null,
        ];

        $changes['tags'] = [
          'added' => array_map($mapTag, $added),
          'removed' => array_map($mapTag, $removed),
        ];
      }
    }

    if (empty($changes)) {
      return null;
    }

    return [
      'changes' => $changes,
    ];
  }

  public function fetchAndCombineTasks(Request $request, string $view)
  {
    $hasFilterByStatus = false;
    $relationships = [
      'visit' => fn($q) => $q->with(['patient', 'bed.room']),
      'tags',
      'status',
      'taskType' => fn($q) => $q->with(['assets', 'teams', 'requestingTeams']),
      'space',
      'spaceTo',
      'assignees',
      'teams' => fn($q) => $q->select('teams.id', 'teams.name'),
    ];

    $filters = $request->input('filters', []);
    $sorters = $request->input('sorters', []);
    $hasFilters = !empty($filters);
    $perPage = min((int) $request->input('perPage', 100), 200);

    $user = Auth::user();

    $query = Task::query()
      ->with($relationships)
      ->select('tasks.*')
      ->distinct();

    // Base scope depending on selected mode
    if ($view === 'requested-tasks') {
      $query->byRequestedTasks($user);
    } else {
      $query->byTasksToExecute($user);
    }

    if ($hasFilters) {
      $this->applyFilters($query, $filters, $hasFilterByStatus, $request);
    }

    if (!$hasFilterByStatus && !$hasFilters) {
      $query->byActive();
    }

    if (!empty($sorters)) {
      foreach ($sorters as $sorter) {
        $field = $sorter['field'] ?? null;
        $dir   = strtolower($sorter['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if ($field === 'status.name') {
          $query->leftJoin('task_statuses', 'tasks.status_id', '=', 'task_statuses.id')
            ->orderBy('task_statuses.name', $dir);
        } elseif ($field === 'task_type.name') {
          $query->leftJoin('task_types', 'tasks.task_type_id', '=', 'task_types.id')
            ->orderBy('task_types.name', $dir);
        } elseif ($field) {
          $allowed = ['id', 'start_date_time', 'status_id', 'task_type_id', 'help_requested', 'created_at', 'updated_at'];
          if (in_array($field, $allowed, true)) {
            $query->orderBy("tasks.$field", $dir);
          }
        }
      }
    } elseif ($hasFilters) {
      $query->orderByDesc('tasks.start_date_time');
    }

    $query
      ->withExists([
        'assignees as assigned_to_me' => fn($q) => $q->whereKey(Auth::id()),
      ])
      ->orderByDesc('assigned_to_me')
      ->orderByDesc(DB::raw('COALESCE(tasks.help_requested, 0)'))
      ->orderByDesc(DB::raw('tasks.task_type_id = 5'))
      ->orderBy('tasks.status_id')
      ->orderByDesc('tasks.start_date_time');

    $tasks = $query->paginate($perPage);

    return [
      'total' => $tasks->total(),
      'per_page' => $tasks->perPage(),
      'current_page' => $tasks->currentPage(),
      'last_page' => $tasks->lastPage(),
      'from' => $tasks->firstItem(),
      'to' => $tasks->lastItem(),
      'data' => collect($tasks->items())->values(),
    ];
  }

  /**
   * Apply filters coming from FilterBar.
   * Expected filter shape: [{ field, type, value }, ...]
   */
  protected function applyFilters($query, array $filters, bool &$hasFilterByStatus, Request $request): void
  {
    // Helper: normalize string for LIKE
    $like = function (string $mode, string $value) {
      $value = trim($value);

      return match ($mode) {
        'startsWith' => [$value . '%'],
        'endsWith'   => ['%' . $value],
        'equals'     => [$value],
        default      => ['%' . $value . '%'], // contains / like
      };
    };

    foreach ($filters as $filter) {
      $field = $filter['field'] ?? null;
      $type  = $filter['type'] ?? 'contains';
      $value = $filter['value'] ?? null;

      if ($field === null) continue;

      // ---- keyword (task name or description)
      if ($field === 'keyword' && filled($value)) {
        $pattern = $like($type, (string) $value);

        $query->where(function ($subQuery) use ($pattern) {
          $subQuery
            ->where('tasks.name', 'like', $pattern)
            ->orWhere('tasks.description', 'like', $pattern);
        });

        continue;
      }

      // ---- assignedTo (name search)
      if ($field === 'assignedTo' && filled($value)) {
        $pattern = $like($type, (string) $value);

        $query->whereHas('assignees', function ($subQuery) use ($pattern) {
          $subQuery->where(function ($q) use ($pattern) {
            $q->where('firstname', 'like', $pattern)
              ->orWhere('lastname', 'like', $pattern);
          });
        });
        continue;
      }

      // status_id (accepts both ID and name)
      if ($field === 'status_id' && filled($value)) {
        $hasFilterByStatus = true;
        // If $value is already numeric it will be used directly, otherwise convert from name to ID
        $statusId = is_numeric($value)
          ? (int) $value
          : TaskStatusEnum::fromCaseName($value)->value;

        $query->where('tasks.status_id', '=', $statusId);
        continue;
      }

      // ---- team_id
      if ($field === 'team_id' && filled($value)) {
        $query->whereHas('teams', function ($teamQuery) use ($value) {
          $teamQuery->where('teams.id', '=', $value);
        });
        continue;
      }

      // ---- onlyAssignedToMe (boolean)
      if ($field === 'onlyAssignedToMe') {
        // Handle "true"/true/1
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        if ($bool) {
          $userId = $request->user()?->id;
          if ($userId) {
            $query->whereHas('assignees', fn($q) => $q->where('users.id', '=', $userId));
          }
        }
        continue;
      }

      // ---- dateRange (value: {from,to})
      if ($field === 'dateRange' && is_array($value)) {
        $tz = config('app.timezone'); // e.g. Europe/Brussels

        $fromRaw = $value['from'] ?? null;
        $toRaw   = $value['to'] ?? null;

        // JS Date -> JSON becomes ISO UTC string with "Z"
        // Parse as instant, convert to app tz, then clamp to local day bounds
        $from = $fromRaw
          ? CarbonImmutable::parse($fromRaw)   // respects "Z" as UTC
          ->setTimezone($tz)
          ->startOfDay()
          : null;

        $to = $toRaw
          ? CarbonImmutable::parse($toRaw)
          ->setTimezone($tz)
          ->endOfDay()
          : CarbonImmutable::parse($value['from'])->setTimezone($tz)->endOfDay();

        // DB stored local time => compare local bounds directly (NO utc())
        if ($from && $to) {
          $query->whereBetween('tasks.start_date_time', [$from, $to]);
        }

        continue;
      }
    }
  }
}
