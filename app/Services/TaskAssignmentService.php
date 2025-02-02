<?php

namespace App\Services;

use App\Models\TaskAssignmentRule;
use App\Models\Team;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskAssignmentService
{
  public static function getTeamsMatchingAssignmentRules(Task $task): Collection
  {
    // Fetch all TaskAssignmentRules with related teams
    $rules = TaskAssignmentRule::with('teams')->get();

    // Initialize an array to hold teams for assignment
    $assignedTeams = collect();

    // Loop through each rule
    foreach ($rules as $rule) {

      // JSON columns are converted to arrays via the model casting
      $taskTypes = collect($rule->task_types);
      $spaces = collect($rule->spaces);
      $spaceTos = collect($rule->spaces_to);
      $campusIds = collect($rule->campuses); // Collect campuses from the rule

      // Fetch the campuses for the task's space and space_to. 
      //I COMMENTED THIS OUT AS DONT SEE THE CASE FOR THIS WHERE A LOCATION SHOULD CHECK THE SELECTED CAMPUS OF THE TASKPLANNER AS A USER NORMALLY WOULDNT CHOOSE FOR EXAMPLE CA IN TASKPLANNER AND THEN A SPACE WITH CAMPUS CD.
      // $taskSpaceCampusId = $task->spaces
      //   ? DB::connection('spaces')->table('spaces')->where('id', $task->spaces)->value('campuses')
      //   : null;

      // $taskSpaceToCampusId = $task->spaces_to
      //   ? DB::table('spaces')->where('id', $task->spaces_to)->value('campuses')
      //   : null;

      $taskSpaceCampusId = null;
      $taskSpaceToCampusId = null;

      $matchesCampus = $campusIds->isNotEmpty() ? $campusIds->contains('id', (string) $task->campus_id) : true;

      // Check if each criterion matches the task, but only if the criterion is specified in the rule
      $matchesTaskType = $taskTypes->isNotEmpty() ? $taskTypes->contains('id', (string) $task->task_type_id) : true;

      // Check if space and space_to match rule spaces
      $matchesSpace = $spaces->isNotEmpty() ? $spaces->contains('id', (string) $task->space_id) : true;
      $matchesSpaceTo = $spaceTos->isNotEmpty() ? $spaceTos->contains('id', (string) $task->space_to_id) : true;

      // Validate campuses space, and space_to

      if (!$taskSpaceCampusId || (!$taskSpaceToCampusId && $task->space_to_id)) {
        //Log::error("In the Task Assignment Service, the campus for the task space(from or to) with ID: $task->id could not be determined");
      }

      $spaceCampusMatch = !$taskSpaceCampusId || $campusIds->contains('id', (string) $taskSpaceCampusId);
      $spaceToCampusMatch = !$taskSpaceToCampusId || $campusIds->contains('id', (string) $taskSpaceToCampusId);

      // Only proceed if the rule matches all specified criteria, including campus constraints
      if (
        $matchesTaskType &&
        $matchesSpace &&
        $matchesSpaceTo &&
        $matchesCampus &&
        $spaceCampusMatch &&
        $spaceToCampusMatch
      ) {

        foreach ($rule->teams as $team) {
          // Check if the team is already added to the collection
          if (!$assignedTeams->contains('id', $team->id)) {
            $assignedTeams->push($team);
          }
        }
      }
    }

    return $assignedTeams;
  }

  public static function assignTaskToTeams(Task $task): void
  {
    $teams = self::getTeamsMatchingAssignmentRules($task);

    // Attach the task to each matched team
    if (!$teams->isEmpty()) {
      $task->teams()->sync($teams->pluck('id'));
    }

    // Assign the task to the residual group if no teams can be assigned based on the rules
    if ($teams->isEmpty()) {
      $task->teams()->attach(config('app.system_team_id'));
    }
  }
}