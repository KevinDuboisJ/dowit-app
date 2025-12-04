<?php

namespace App\Models;

use App\Contracts\HasRequestingTeamsScopeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Asset;
use App\Traits\HasTeams;
use App\Traits\HasCreator;
use App\Traits\HasAccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskType extends Model implements HasRequestingTeamsScopeInterface
{
    use HasFactory, HasCreator, HasTeams, HasAccessScope;

    protected $fillable = ['name', 'team_id'];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class);
    }

    public function requestingTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'task_type_requesting_team');
    }

    public function scopeByRequestingTeams(Builder $query, User $user): Builder
    {
        $teamIds = $user->getTeamIds();

        if (empty($teamIds)) {
            return $query;
        }

        return $query->whereHas('requestingTeams', function (Builder $q) use ($teamIds) {
            $q->whereIn('teams.id', $teamIds);
        });
    }
}
