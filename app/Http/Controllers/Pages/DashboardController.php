<?php

namespace App\Http\Controllers\Pages;

use App\Enums\TaskPriorityEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\TaskAssignmentService;
use App\Models\Announcement;
use App\Models\Tag;
use App\Services\TaskService;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\User;

class DashboardController extends Controller
{

  public function index(Request $request, TaskService $taskService)
  {
    return $this->renderDashboard($request, $taskService, 'tasks');
  }

  public function requestedTasks(Request $request, TaskService $taskService)
  {
    return $this->renderDashboard($request, $taskService, 'requested-tasks');
  }

  private function renderDashboard(Request $request, TaskService $taskService, string $view)
  {
    $user = Auth::user();
    $settings = $user->getSettings();
    $userTeamIds = $user->getTeamIds();

    return Inertia::render('Dashboard', [
      'tasks' => fn() => $taskService->fetchAndCombineTasks($request, $view),

      'settings' => fn() => $settings,

      'statuses' => fn() => DB::table('task_statuses')
        ->select('id', 'name')
        ->whereIn('id', [1, 4, 5, 6, 7])
        ->get()
        ->map(function ($status) {

          $enum = TaskStatusEnum::tryFrom($status->id);

          return [
            'name' => $enum->name,
            'label' => $enum?->getLabel() ?? $status->name,
          ];
        }),

      'campuses' => Inertia::optional(function () {
        return DB::table('campuses')
          ->select('id', 'name')
          ->get()->map(function ($item) {
            return [
              'value' => $item->id,
              'label' => $item->name,
            ];
          });
      }),

      'tags' => Tag::select('id', 'name', 'icon')->get()->map(function ($item) {
        return [
          'value' => $item->id,
          'label' => $item->name,
        ];
      }),

      'assets' => Inertia::optional(function () {
        return DB::table('tags')
          ->select('id', 'name')
          ->get()->map(function ($item) {
            return [
              'value' => $item->id,
              'label' => $item->name,
            ];
          });
      }),

      'task_types' => Inertia::optional(function () use ($userTeamIds) {
        return TaskType::query()
          ->where('is_system', false)
          ->whereHas('requestingTeams', function ($q) use ($userTeamIds) {
            $q->whereIn('teams.id', $userTeamIds);
          })
          ->get()
          ->mapWithKeys(fn($t) => [
            $t->id => [
              'name' => $t->name,
              'value' => $t->id,
              'isPatientTransport' => TaskTypeEnum::tryFrom($t->id)?->isPatientTransport(),
            ],
          ]);
      }),

      'teamsMatchingAssignmentRules' => Inertia::optional(function () use ($request) {

        $task = new Task([
          'campus_id' => $request->input('campus') ?? null,
          'task_type_id' => $request->input('taskType'),
          'space_id' => $request->input('space') ?? null,
          'space_to_id' => $request->input('spaceTo') ?? null,
        ]);

        $task->setRelation(
          'tags',
          collect($request->input('tags') ?? [])
            ->map(fn($id) => ['id' => (int) $id])
        );

        return TaskAssignmentService::getAssignmentRuleTeamsByTaskMatchAndTeams($task)->get();
      }),

      'announcements' => Comment::with('creator')
        ->where(function ($query) use ($userTeamIds) {
          if (!empty($userTeamIds)) {
            $query->whereRaw(implode(' OR ', array_map(function ($teamId) {
              return "JSON_CONTAINS(recipient_teams, '$teamId')";
            }, $userTeamIds)));
          }
          $query->orWhereJsonContains('recipient_users', Auth::id());
        })
        ->where(function ($query) {
          $query->whereJsonDoesntContain('read_by', Auth::id())
            ->orWhereNull('read_by');
        })
        ->where(function ($query) {
          $today = Carbon::now()->toDateString();
          $query->where('start_date', '<=', $today)
            ->where(function ($q2) use ($today) {
              $q2->where('end_date', '>=', $today)
                ->orWhereNull('end_date');
            });
        })
        ->whereNull('deleted_at')
        ->orderBy('start_date')
        ->get(),

      'ownAnnouncements' => Inertia::optional(function () {
        // 1) Get announcements
        $announcements = Announcement::where('created_by', Auth::id())
          ->whereNull('deleted_at')
          ->orderBy('created_at', 'desc')
          ->get();

        // 2) Collect all user + team IDs from all announcements
        $userIds = $announcements->pluck('recipient_users') // collection of arrays
          ->flatten()
          ->filter()
          ->unique()
          ->values()
          ->all();

        $teamIds = $announcements->pluck('recipient_teams')
          ->flatten()
          ->filter()
          ->unique()
          ->values()
          ->all();

        // 3) Load all users/teams in one go and index by ID
        $users = User::whereIn('id', $userIds)
          ->select('id', 'firstname', 'lastname')
          ->get()
          ->keyBy('id');

        $teams = Team::whereIn('id', $teamIds)
          ->select('id', 'name')
          ->get()
          ->keyBy('id');

        // 4) Attach users[] and teams[] to each announcement
        return $announcements->map(function ($announcement) use ($users, $teams) {
          $announcement->users = collect($announcement->recipient_users ?? [])
            ->map(fn($id) => $users->get($id))
            ->filter()
            ->map(fn($user) => [
              'value' => $user->id,
              'label' => "{$user->firstname} {$user->lastname}",
            ])
            ->values()
            ->all();

          $announcement->teams = collect($announcement->recipient_teams ?? [])
            ->map(fn($id) => $teams->get($id))
            ->filter()
            ->map(fn($team) => [
              'value' => $team->id,
              'label' => $team->name,
            ])
            ->values()
            ->all();

          return $announcement;
        });
      }),

      'priorities' => array_column(TaskPriorityEnum::cases(), 'value'),
    ]);
  }
}
