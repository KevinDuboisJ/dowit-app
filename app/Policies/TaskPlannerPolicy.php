<?php

namespace App\Policies;

use App\Models\TaskPlanner;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TaskPlannerPolicy
{
     /**
     * Run before any other check: if this returns non-null, that result is used.
     */
    public function before(User $user, string $ability)
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(): bool
    {
        return true; // Defined by the global scope in the HasTeams trait
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TaskPlanner $taskPlanner): bool
    {
        return $user->userBelongsToAtLeastOneTeam($taskPlanner->teams->pluck('id')->toArray()) || $taskPlanner->isCreator() || $taskPlanner->isAssignedTo(Auth::user());
    }

    public function delete(User $user, TaskPlanner $taskPlanner): bool
    {
        return $user->userBelongsToAllTeams($taskPlanner->teams->pluck('id')->toArray()) || $taskPlanner->isCreator() || $taskPlanner->isAssignedTo(Auth::user());
    }
}
