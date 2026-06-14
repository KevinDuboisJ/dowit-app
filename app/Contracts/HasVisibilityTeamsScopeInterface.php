<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface HasVisibilityTeamsScopeInterface
{
  public function scopeByVisibilityTeams(Builder $query, array $teamIds): Builder;
}
