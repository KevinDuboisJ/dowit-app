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
        // if ($user->isSuperAdmin()) {
        //     return true;
        // }

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
        $teamIds = $user->getTeamIds();

        $isUnassigned = $task->assignees->isEmpty();
        $isAssignee = $task->assignees->contains('id', $user->id);
        $isAdmin = $user->isAdmin();

        $isRequestingTeam = $task->taskType?->requestingTeams
            ?->pluck('id')
            ->intersect($teamIds)
            ->isNotEmpty() ?? false;

        return $isUnassigned || $isAssignee || $isAdmin || $isRequestingTeam;
    }

    public function execute(User $user, Task $task): bool
    {
        $teamIds = $user->getTeamIds();

        return $task->taskType?->teams
            ?->pluck('id')
            ->intersect($teamIds)
            ->isNotEmpty() ?? false;
    }

    public function reject(User $user): bool
    {
        // Allow if the user is admin and belongs to team with ID 3 (e.g., Schoonmaak CA)
        return $user->isAdmin();
    }
}
