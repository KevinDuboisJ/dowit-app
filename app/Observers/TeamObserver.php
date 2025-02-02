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
        $teamService = new TeamService();
        // Only trigger sync for teams that have automatic assignment
        if ($team->wasChanged('autoassign_rules') && $team->autoassign_rules !== null) {
            $teamService = new TeamService();
            $teamService->syncUsersToTeam($team, User::all());
        }
    }
}
