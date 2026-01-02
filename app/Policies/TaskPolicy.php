<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can view the task. This is handled by the HasAccessScope
     */
    public function view(): bool
    {
        return true;
    }

    /**
     *  Allow updates when the task is unassigned, the user is assigned to the task, or the user is an admin
     *  Disallow updates when the task type is request-only for the user
     */
    public function update(User $user, Task $task): bool
    {
        $userTeamIds = $user->getTeamIds();

        if ($task?->taskType?->requestingTeams()
            ->whereIn('teams.id', $userTeamIds)
            ->exists()
        ) {
            return false;
        }

        return $task->assignees->isEmpty() || $task->assignees->contains($user) || $user->isAdmin();
    }

    public function reject(User $user): bool
    {
        // Allow if the user is admin and belongs to team with ID 3 (e.g., Schoonmaak CA)
        return $user->isAdmin() && $user->belongsToTeam(3);
    }
}
