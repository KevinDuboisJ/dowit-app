<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Asset;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskType extends Model
{
    use HasFactory, HasCreator;

    protected $fillable = ['name', 'team_id'];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class);
    }

    public function availableToTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'task_type_requesting_team');
    }

    public function scopeByAvailableToTeams(Builder $query, array $teamIds): Builder
    {
        return $query->whereHas('availableToTeams', function (Builder $q) use ($teamIds) {
            $q->whereIn('teams.id', $teamIds);
        });
    }
}
