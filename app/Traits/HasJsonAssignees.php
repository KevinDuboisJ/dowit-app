<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait HasJsonAssignees
{
    public function scopeByAssignees($query, User $user)
    {
        return $query->whereJsonContains('assignments->users', (string) $user->id);
    }

    public function isAssignedTo(User $user): bool
    {
        if ($user === null) {
            $user = Auth::user();
        }

        return !empty($this->assignments['users'])
            && is_array($this->assignments['users'])
            && in_array($user->id, $this->assignments['users']);
    }
}
