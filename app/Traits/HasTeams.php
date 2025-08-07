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
     * The `teams` pivot relationship.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeByBelongsToTeamIds(Builder $query, array $teamIds): Builder
    {
        if (count($teamIds) === 0) {
            return $query->whereRaw('0 = 1');
        }

        $relation = method_exists($this, 'teamRelationPath')
            ? $this->teamRelationPath()
            : 'teams';

        return $query->whereHas($relation, function ($q) use ($teamIds) {
            $q->whereIn('teams.id', $teamIds);
        });
    }
}
