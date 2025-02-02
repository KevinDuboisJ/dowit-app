<?php

namespace App\Http\Controllers\Pages;

use App\Enums\TaskStatus as TaskStatusEnum;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Services\TaskAssignmentService;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\OAZIS\Patient;
use App\Events\BroadcastEvent;

class DashboardController extends Controller
{
  public function index(Request $request)
  {
    logger($request);
    $user = Auth::user();
    $settings = $user->getSettings();
    $userTeamsIds = $user->getTeamIds();

    //$settings = PrioritySettingResource::collection($settings)->toArray(request());

    return Inertia::render('Dashboard', [
      'tasks' => fn() => $this->fetchAndCombineTasks($request, $user),
      'settings' => fn() => $settings,
      'statuses' => fn() => DB::table('task_statuses')
        ->select('id', 'name')
        ->whereIn('id', [1, 2, 4, 5, 6, 12])
        ->get(),

      'teams' => fn() =>
      DB::table('teams')
        ->select('id', 'name')
        ->whereIn('id', $userTeamsIds)
        ->when($request->has('name'), function ($query) use ($request) {
          $query->where('name', 'like', "{$request->userInput}%");
        })
        ->get()->map(function ($item) {
          return [
            'value' => $item->id,
            'label' => $item->name,
          ];
        }),

      'campuses' => Inertia::lazy(function () {
        return DB::table('campuses')
          ->select('id', 'name')
          ->get()->map(function ($item) {
            return [
              'value' => $item->id,
              'label' => $item->name,
            ];
          });
      }),

      'task_types' => Inertia::lazy(function () {
        return DB::table('task_types')
          ->select('id', 'name')
          ->get()->map(function ($item) {
            return [
              'value' => $item->id,
              'label' => $item->name,
            ];
          });
      }),

      'teamsMatchingAssignmentRules' => Inertia::lazy(function () use ($request) {
        return TaskAssignmentService::getTeamsMatchingAssignmentRules(new Task([
          'campus_id' => $request->input('campus') ?? null,
          'task_type_id' => $request->input('taskType') ?? null,
          'space_id' => $request->input('space') ?? null,
          'space_to_id' => $request->input('spaceTo') ?? null,
        ]));
      }),

      'patients' => Inertia::lazy(function () use ($request) {
        return TaskAssignmentService::getTeamsMatchingAssignmentRules(new Task([
          'campus_id' => $request->input('campus') ?? null,
          'task_type_id' => $request->input('taskType') ?? null,
          'space_id' => $request->input('space') ?? null,
          'space_to_id' => $request->input('spaceTo') ?? null,
        ]));
      }),

      'announcements' => fn() => DB::table('comments')
        ->select('id', 'content', 'start_date', 'end_date', 'recipient_teams', 'recipient_users', 'read_by')
        ->where(function ($query) use ($userTeamsIds) {
          if (!empty($userTeamsIds)) {
            $query->whereRaw(
              implode(' OR ', array_map(function ($teamId) {
                return "JSON_CONTAINS(recipient_teams, '$teamId')";
              }, $userTeamsIds))
            );
          }

          $query->orWhereJsonContains('recipient_users', Auth::user()->id);
        })
        ->where(function ($query) {
          $query->whereJsonDoesntContain('read_by', Auth::user()->id)
            ->orWhereNull('read_by');
        })
        ->where(function ($query) {
          $query->where('start_date', '<=', Carbon::now()->toDateString())
            ->where(function ($subQuery) {
              $subQuery->where('end_date', '>=', Carbon::now()->toDateString())
                ->orWhereNull('end_date');
            });
        })
        ->orderBy('start_date')
        ->get(),
    ]);
  }

  public function markAsRead(Comment $comment, Authenticatable $user)
  {
    // Add the user ID to the `read_by` column if it's not already there
    $readBy = $comment->read_by ?? [];
    if (!in_array($user->id, $readBy)) {
      $readBy[] = $user->id;
      $comment->read_by = $readBy;
      $comment->save();
    }

    return to_route('dashboard.index'); // return redirect()->back();
  }

  public function announce(StoreAnnouncementRequest $request)
  {
    $data = $request->prepareForDatabase();
    $announcement = Comment::create($data);
    broadcast(new BroadcastEvent($announcement, 'announcement_created'));
    return response()->json($announcement);
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
          'assignedUsers',
          fn($subQuery) =>
          $subQuery->where('firstname', $type, $value . '%')
            ->orWhere('lastname', $type, $value . '%')
        );
      }
    }
  }

  protected function getDefaultExcludedStatuses()
  {
    return [
      TaskStatusEnum::Replaced->value,
      TaskStatusEnum::Completed->value,
      TaskStatusEnum::Skipped->value,
    ];
  }

  private function fetchAndCombineTasks($request, $user)
  {
    $hasFilterByStatus = false;

    // Get common settings
    $relationships = Task::getRelationships();
    $filters = $request->filled('filters') ? $request->input('filters') : null;
    $sorters = $request->filled('sorters') ? $request->input('sorters') : null;
    $defaultExcludedStatuses = $this->getDefaultExcludedStatuses();
 
    // Get assigned tasks (non-paginated)
    $assignedTasks = Task::with($relationships)
      ->byAssigned()
      ->byActive()
      ->when($filters, function ($query) use ($filters, &$hasFilterByStatus) {
        $this->applyFilters($query, $filters, $hasFilterByStatus);
      })
      ->when(!$hasFilterByStatus, function ($query) use ($defaultExcludedStatuses) {
        $query->whereNotIn('status_id', $defaultExcludedStatuses);
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
      ->when(!$hasFilterByStatus, function ($query) use ($defaultExcludedStatuses) {
        $query->whereNotIn('status_id', $defaultExcludedStatuses);
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
      ->paginate($request->input('size', 100));

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
}
