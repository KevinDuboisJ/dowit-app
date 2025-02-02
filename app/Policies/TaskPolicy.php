<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine if the user can view the task.
     */
    public function view(User $user, Task $task): bool
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
        return $task->assignedUsers->contains($user) || $user->isAdmin() || $user->isSuperAdmin();
    }

    public function assign(User $user, Task $task): bool
    {
        // Allow if the user is assigned to the task or if the task is unassigned
        return $task->assignedUsers->contains($user) || $task->assignedUsers->isEmpty() || $user->isAdmin() || $user->isSuperAdmin();
    }

    public function isAssignedToCurrentUser(User $user, Task $task): bool
    {
        // Check if the task is assigned to the current user.
        // If not, the user can click the help button for assistance.
        return $task->assignedUsers->contains($user);
    }
}
