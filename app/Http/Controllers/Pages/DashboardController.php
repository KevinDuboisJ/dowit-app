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
use App\Services\TaskService;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\User;

class DashboardController extends Controller
{
  public function index(Request $request, TaskService $taskService)
  {
    $user = Auth::user();
    $settings = $user->getSettings();
    $userTeamsIds = $user->getTeamIds();

    return Inertia::render('Dashboard', [
      'tasks' => fn() => $taskService->fetchAndCombineTasks($request),

      'settings' => fn() => $settings,

      'statuses' => fn() => DB::table('task_statuses')
        ->select('id', 'name')
        ->whereIn('id', [1, 2, 4, 5, 6, 7, 12])
        ->get(),

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

      'tags' => Inertia::lazy(function () {
        return DB::table('tags')
          ->select('id', 'name')
          ->get()->map(function ($item) {
            return [
              'value' => $item->id,
              'label' => $item->name,
            ];
          });
      }),

      'assets' => Inertia::lazy(function () {
        return DB::table('tags')
          ->select('id', 'name')
          ->get()->map(function ($item) {
            return [
              'value' => $item->id,
              'label' => $item->name,
            ];
          });
      }),

      'task_types' => Inertia::lazy(function () {

        return TaskType::all()->mapWithKeys(fn($t) => [
          $t->id => [
            'name' => $t->name,
            'value' => $t->id,
            'isPatientTransport' => TaskTypeEnum::tryFrom($t->id)?->isPatientTransport(),
          ],
        ]);
      }),

      'teamsMatchingAssignmentRules' => Inertia::lazy(function () use ($request) {

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
        ->where(function ($query) use ($userTeamsIds) {
          if (!empty($userTeamsIds)) {
            $query->whereRaw(implode(' OR ', array_map(function ($teamId) {
              return "JSON_CONTAINS(recipient_teams, '$teamId')";
            }, $userTeamsIds)));
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

      'task_statuses' => [
        TaskStatusEnum::Added->name,
        TaskStatusEnum::InProgress->name,
        TaskStatusEnum::WaitingForSomeone->name,
        TaskStatusEnum::Completed->name,
      ],

    ]);
  }
}
