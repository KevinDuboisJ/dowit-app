<?php

namespace App\Policies;

use App\Models\TaskAssignmentRule;
use App\Models\User;

class TaskAssignmentRulePolicy
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

    public function update(User $user, TaskAssignmentRule $taskAssignmentRule): bool
    {
        return $user->userBelongsToAtLeastOneTeam($taskAssignmentRule->teams->pluck('id')->toArray()) || $taskAssignmentRule->isCreator();
    }

    public function delete(User $user, TaskAssignmentRule $taskAssignmentRule): bool
    {
        return $user->userBelongsToAllTeams($taskAssignmentRule->teams->pluck('id')->toArray()) || $taskAssignmentRule->isCreator();
    }
}
