<?php

namespace App\Services;

use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Collection;

class TeamService
{
  public function syncUsersToTeam(Team $team, Collection $users): void
  {
    foreach ($users as $user) {
      $formattedDepartmentId = $user->formatted_department_id;
      $formattedProfessionId = $user->formatted_profession_id;
      $shouldSync = false;
      $rules = $team->autoassign_rules ?? [];

      foreach ($rules as $rule) {
        // Check if the department matches and profession is null
        if ($rule['department_id'] === $formattedDepartmentId && is_null($rule['profession_id'])) {
          $shouldSync = true;
        }

        // Check if both department and profession match
        if ($rule['department_id'] === $formattedDepartmentId && $rule['profession_id'] === $formattedProfessionId) {
          $shouldSync = true;
        }

        // Check if the profession matches regardless of department
        if ($rule['profession_id'] === $formattedProfessionId) {
          $shouldSync = true;
        }
      }

      // Sync the user to the team if any condition is true
      if ($shouldSync) {
        $user->teams()->syncWithoutDetaching($team->id);
      } else {
        // Detach if the user no longer meets any rule
        $user->teams()->detach($team->id);
      }
    }
  }

  public function autoAssignTeamsToUsers(Collection $users): void
  {
    $teams = Team::all(); // Get all teams with their rules

    foreach ($teams as $team) {
      $this->syncUsersToTeam($team, $users);
    }
  }
}
