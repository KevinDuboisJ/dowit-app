<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Team;

class UserObserver
{
    public function created(User $user)
    {
        // Sync user's teams
        $this->syncTeams($user);
    }

    public function updated(User $user)
    {
        if ($user->is_active && $user->wasChanged(['department_id', 'profession_id'])) {
            // Sync user's teams
            $this->syncTeams($user);
        }
    }

    protected function syncTeams(User $user)
    {
        // Get formatted department and profession IDs
        $formattedDepartmentId = $user->formatted_department_id;
        $formattedProfessionId = $user->formatted_profession_id;

        // Fetch teams with autoassign_rules
        $teams = Team::whereNotNull('autoassign_rules')->get();

        foreach ($teams as $team) {
            $shouldSync = false;

            foreach ($team->autoassign_rules as $rule) {
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
}
