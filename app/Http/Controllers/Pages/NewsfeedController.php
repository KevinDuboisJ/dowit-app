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

    $createdByFilter = Helper::getFilterByValue($filters, 'created_by');
    $teamFilter      = Helper::getFilterByValue($filters, 'team_id');
    $statusFilter    = Helper::getFilterByValue($filters, 'status_id');

    // Main team based filtering is handled via the HasTeams trait
    $newsfeed = Comment::query()
      ->with([
        'creator',
        'task.assignees',
        'status:id,name',
      ])
      ->byTeams()
      ->when(
        $createdByFilter,
        fn(Builder $q) =>
        $this->applyFilters($q, $createdByFilter)
      )
      ->when($teamFilter, function (Builder $q) use ($teamFilter) {
        $teamIds = [$teamFilter['value']];

        $q->where(function (Builder $inner) use ($teamIds) {
          $inner
            ->where(fn(Builder $sub) => $sub->byRecipientTeams($teamIds))
            ->orWhere(fn(Builder $sub) => $sub->byTaskTeams($teamIds));
        });
      })
      ->when(
        $statusFilter,
        fn(Builder $q) =>
        $this->applyFilters($q, $statusFilter)
      )
      ->latest('created_at')
      ->paginate(5)
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
      if (! isset($filter['field'], $filter['type'], $filter['value'])) {
        continue;
      }

      $field    = $filter['field'];
      $operator = $filter['type'];
      $value    = $filter['value'];

      $query->where($field, $operator, $value);
    }
  }
}
