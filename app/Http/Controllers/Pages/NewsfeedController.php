<?php

namespace App\Http\Controllers\Pages;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NewsfeedController extends Controller
{
  public function index(Request $request)
  {
    $filters = (array) $request->input('filters', []);

    // Main team based filtering is handled via the HasTeams trait
    $newsfeed = Comment::query()
      ->with([
        'creator' => fn($q) => $q->withoutGlobalScopes(),
        'task.taskType' => fn($q) => $q->with(['assets', 'teams', 'requestingTeams']),
        'task.assignees',
        'status:id,name',
      ])
      ->byTeams()
      ->when(
        $filters,
        fn(Builder $q) =>
        $this->applyFilters($q, $filters)
      )
      ->latest('created_at')
      ->paginate(20)
      ->withQueryString();

    return Inertia::render('Newsfeed', [
      'newsfeed'  => fn() => $newsfeed,
      'teammates' => fn() => User::byTeams()->get(),
      'statuses'  => fn() => DB::table('task_statuses')
        ->select('id', 'name')
        ->whereIn('id', [1, 2, 4, 5, 6, 12])
        ->orderBy('name')
        ->get(),
    ]);
  }

  protected function applyFilters(Builder $query, array $filters): void
  {
    foreach ($filters as $filter) {

      $field    = $filter['field'];
      $operator = $filter['type'];
      $value    = $filter['value'];

      if (! isset($field, $operator, $value)) {
        continue;
      }

      if ($field === 'team_id') {

        $teamIds = is_array($value) ? $value : [$value];

        $query->where(function (Builder $q) use ($teamIds) {
          $q
            ->where(fn(Builder $sub) => $sub->byRecipientTeams($teamIds))
            ->orWhere(fn(Builder $sub) => $sub->byTaskTeams($teamIds));
        });

        continue;
      }

      $query->where($field, $operator, $value);
    }
  }
}
