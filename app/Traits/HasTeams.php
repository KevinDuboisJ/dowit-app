<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasTeams
{

  public static function getEloquentQuery(): Builder
  {
    $query = parent::getEloquentQuery();

    // Check if the authenticated user has the SUPER_ADMIN role && apply the byUserTeams scope if the user is not a SUPER_ADMIN
    $user = Auth::user();

    if ($user->hasRole('SUPER_ADMIN')) {
      return $query;
    }

    return $query->byUserTeams();
  }
}
