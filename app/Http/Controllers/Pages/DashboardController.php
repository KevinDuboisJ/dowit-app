<?php

namespace App\Http\Controllers\Pages;

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
use App\Services\TaskService;

class DashboardController extends Controller
{
  public function index(Request $request, TaskService $taskService)
  {
    $user = Auth::user();
    $settings = $user->getSettings();
    $userTeamsIds = $user->getTeamIds();

    //$settings = PrioritySettingResource::collection($settings)->toArray(request());

    return Inertia::render('Dashboard', [
      'tasks' => fn() => $taskService->fetchAndCombineTasks($request),
      'settings' => fn() => $settings,
      'statuses' => fn() => DB::table('task_statuses')
        ->select('id', 'name')
        ->whereIn('id', [1, 2, 4, 5, 6, 12])
        ->get(),

      'teams' => fn() => $user->getTeams(),

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
        return TaskAssignmentService::getAssignmentRulesByTaskMatchAndTeams(new Task([
          'campus_id' => $request->input('campus') ?? null,
          'task_type_id' => $request->input('taskType') ?? null,
          'space_id' => $request->input('space') ?? null,
          'space_to_id' => $request->input('spaceTo') ?? null,
        ]), Auth::user()->teams->pluck('id')->all());
      }),

      'patients' => Inertia::lazy(function () use ($request) {
        return TaskAssignmentService::getAssignmentRulesByTaskMatchAndTeams(new Task([
          'campus_id' => $request->input('campus') ?? null,
          'task_type_id' => $request->input('taskType') ?? null,
          'space_id' => $request->input('space') ?? null,
          'space_to_id' => $request->input('spaceTo') ?? null,
        ]), Auth::user()->teams->pluck('id')->all());
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

}
