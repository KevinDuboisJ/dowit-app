<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

interface Teams
{
  // Authenticate a user based on the provided username and password.
  public function scopeByUserTeams(Builder $query): Builder;
}
