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
     * Determine if the user can view the task.
     */
    public function view(): bool
    {
        // Allow if the user is assigned to the task or if the task is unassigned
        return true;
    }

    /**
     * Determine if the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        // Allow if the user is assigned to the task
        return $task->assignees->contains($user) || $user->isAdmin();
    }

    public function modify(User $user, Task $task): bool
    {
        $userTeamIds = $user->getTeamIds();

        return !$task->taskType
            ->requestingTeams()
            ->whereIn('teams.id', $userTeamIds)
            ->exists();
    }

    public function reject(User $user): bool
    {
        // Allow if the user is admin and belongs to team with ID 3 (e.g., Schoonmaak CA)
        return $user->isAdmin() && $user->belongsToTeam(3);
    }

    public function assign(User $user, Task $task): bool
    {
        // Allow if the user is assigned to the task or if the task is unassigned
        return $task->assignees->contains($user) || $task->assignees->isEmpty() || $user->isAdmin() || $user->isSuperAdmin();
    }

    public function isAssignedToCurrentUser(User $user, Task $task): bool
    {
        // Check if the task is assigned to the current user.
        // If not, the user can click the help button for assistance.
        return $task->assignees->contains($user);
    }
}
