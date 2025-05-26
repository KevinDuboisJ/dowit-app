<?php

namespace App\Services;

use App\Models\TaskAssignmentRule;
use App\Models\Team;
use App\Models\Task;
use Illuminate\Support\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TaskAssignmentService
{
  /**
   * Retrieve all Teams that match both the AssignmentRules for the given Task
   *
   * @param  Task   $task
   * @param  int[]  $allowedTeamsScopeIds only include these teams
   * @return Collection|\App\Models\Team[]
   */
  public static function getAssignmentRulesByTaskMatchAndTeams(Task $task, array $allowedTeamsScopeIds): Collection {

    return TaskAssignmentRule::with('teams')
      ->byBelongsToTeamIds($allowedTeamsScopeIds)
      ->byTaskMatch($task);

      
      // ->get()
      // ->flatMap->teams
      // ->unique('id')
      // ->values();
  }

  public static function assignTaskToTeams(Task $task, array $allowedTeamsScopeIds): void
  {
    $teams = self::getAssignmentRulesByTaskMatchAndTeams($task, $allowedTeamsScopeIds);

    // Attach the task to each matched team
    if (!$teams->isEmpty()) {
      $task->teams()->sync($teams->pluck('id'));
    }

    // Assign the task to the residual group if no teams can be assigned based on the rules
    if ($teams->isEmpty()) {
      $task->teams()->attach(config('app.system_team_id'));
    }
  }

  public static function getTeamsFromTheAssignmentRulesByTaskMatchAndTeams(Task $task, array $allowedTeamsScopeIds) {
    return Team::whereHas('taskAssignmentRules', function ($query) use ($task, $allowedTeamsScopeIds) {
      $query->byBelongsToTeamIds($allowedTeamsScopeIds)
            ->byTaskMatch($task);
  })->distinct();
  }
  
}
