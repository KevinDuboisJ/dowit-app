<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Auth\AuthenticationException;
use App\Models\Team;
use App\Models\User;

trait HasTeams
{
    /**
     * Global scope: if the authenticated user is NOT super_admin,
     * only return records linked to their teams.
     */
    public static function bootHasTeams(): void
    {
        static::addGlobalScope('team_access', function (Builder $query) {
            if (static::class === User::class) {
                return;
            }

            $user = Auth::user();

            if (! $user || $user->isSuperAdmin()) {
                return;
            }

            /** @var self $model */
            $model = new static;

            $query->where(function ($query) use ($model, $user) {
                $model->scopeByOwnOrBelongsToUserTeams($query, $user);

                // If the model has an 'assignees' relationship, include assigned tasks
                if (method_exists($model, 'assignees')) {
                    $query->orWhereHas('assignees', function ($q2) use ($user) {
                        $q2->where('users.id', $user->id);
                    });
                }
            });
        });
    }

    /**
     * The `teams` pivot relationship.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    // Team.php
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeByOwnOrBelongsToUserTeams(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $this->scopeByCreator($q, $user)
                ->orWhere(function ($q2) use ($user) {
                    $this->scopeByBelongsToTeamIds($q2, $user->teams->pluck('id')->toArray());
                });
        });
    }

    public function scopeByCreator(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->id);
    }

    public function scopeByBelongsToTeamIds(Builder $query, array $teamIds): Builder
    {
        // No team IDs → short-circuit to an always-false where clause. If the given array is empty, this will match no records
        if (count($teamIds) === 0) {
            return $query->whereRaw('0 = 1');
        }

        $query->whereHas('teams', function ($query) use ($teamIds) {
            $query->whereIn('team_id', $teamIds);
        });

        return $query;
    }
}