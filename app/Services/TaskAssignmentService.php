<?php

namespace App\Services;

use App\Models\TaskAssignmentRule;
use App\Models\Team;
use App\Models\Task;
use Illuminate\Support\Collection;

class TaskAssignmentService
{
  /**
   * Retrieve all Teams that match both the AssignmentRules for the given Task
   *
   * @param  Task   $task
   * @param  int[]  $allowedTeamsScopeIds only include these teams
   * @return Collection|\App\Models\Team[]
   */
  public static function getAssignmentRuleTeamsByTaskMatchAndTeams(Task $task)
  {
    return Team::whereHas('taskAssignmentRules', function ($query) use ($task) {
      $query->byTaskMatch($task);
    })
      ->select('id', 'name')
      ->distinct();
  }
}
