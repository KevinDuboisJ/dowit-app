<?php

namespace App\Traits;

use App\Models\User;

trait HasAssignees
{
    public function assignees()
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeByAssignees($query, User $user)
    {
        $query->whereHas('assignees', function ($userQuery) use ($user) {
            $userQuery->where('users.id', $user->id);
        });
    }

    public function scopeWithoutAssignees($query, User $user)
    {
        $query->whereDoesntHave('assignees', function ($userQuery) use ($user) {
            $userQuery->where('users.id', $user->id);
        });
    }

    public function syncAssignees(array $ids)
    {
        $this->assignees()->sync($ids);
    }
}
