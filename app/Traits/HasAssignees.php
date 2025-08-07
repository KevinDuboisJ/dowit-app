<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

    public function assignUsers(array $userIds)
    {
        $this->assignees()->syncWithoutDetaching($userIds);
    }

    public function unassignUsers(array $userIds)
    {
        $this->assignees()->detach($userIds);
    }
}
