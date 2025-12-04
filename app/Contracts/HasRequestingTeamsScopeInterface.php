<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface HasRequestingTeamsScopeInterface
{
  public function scopeByRequestingTeams(Builder $query, User $user): Builder;
}
