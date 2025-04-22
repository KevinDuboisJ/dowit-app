<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Services\TeamService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use App\Models\User;

class NewsfeedController extends Controller
{

  public function index(Request $request)
  {
    logger($request);
    $filters = $request->input('filters', []);
    $user = Auth::user();

    $newsfeed = Comment::with([
      'user',
      'task' => fn($query) => $query->with(['assignees']),
      'status' => fn($query) => $query->select('id', 'name')
    ])
      ->when(!Helper::containsFilter($filters, 'user_id') && !Helper::containsFilter($filters, 'team_id'), function ($query) use ($user) {

        // Apply the default scope to filter by the user's teams
        $query->byUserTeams($user);
      })
      ->when($filter = Helper::containsFilter($filters, 'user_id'), function ($query) use ($filter) {
        $this->applyFilters($query, $filter);
      })
      ->when($filter = Helper::containsFilter($filters, 'team_id'), function ($query) use ($filter) {
        $query->whereHas('user.teams', function ($query) use ($filter) {
          $this->applyFilters($query, $filter);
        });
        $query->whereHas('task.teams', function ($query) use ($filter) {
          $this->applyFilters($query, $filter);
        });
      })
      ->when($filter = Helper::containsFilter($filters, 'status_id'), function ($query) use ($filter) {
        $this->applyFilters($query, $filter);
      })
      ->orderBy('created_at', 'DESC')->paginate(5);

    return Inertia::render('Newsfeed', [
      'newsfeed' => fn() => $newsfeed,
      'teammates' => fn() => $user->getTeammates(),
      'statuses' => fn() => DB::table('task_statuses')->select('id', 'name')->whereIn('id', [1, 2, 4, 5, 6, 12])->get(),
      'teams' => fn() => $user->getTeams(),
    ]);
  }

  protected function applyFilters($query, $filters)
  {
    foreach ($filters as $filter) {
      $field = $filter['field'];
      $type = $filter['type'];
      $value = $filter['value'];

      $query->where($field, $type, $value);
    }
  }
}
