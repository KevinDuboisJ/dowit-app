<?php

namespace App\Observers;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;

class TeamObserver
{
    public function created(Team $team)
    {
        // Only trigger sync for teams that have automatic assignment
        if ($team->autoassign_rules !== null) {
            $teamService = new TeamService();
            $teamService->syncUsersToTeam($team, User::all());
        }
    }

    public function updated(Team $team)
    {
        // Reconcile membership even when the last rule has been removed.
        if ($team->wasChanged('autoassign_rules')) {
            $teamService = new TeamService();
            $teamService->syncUsersToTeam($team, User::all());
        }
    }
}
