<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\AuthenticationException;

class Role extends Model
{
  use HasFactory;

  protected $guarded = [];

  public function permissions()
  {
    return $this->belongsToMany(Permission::class);
  }

  public function getRolesUsingLdapGroups(array $entries)
  {
    $roles = collect($entries)
      ->reject(fn($key) => $key === 'count')
      ->map(fn($value) => explode(',', str_replace('CN=', '', $value))[0])
      ->flatMap(function ($ldapGroupName) {
        return $this->access->byGroup($ldapGroupName)->with('roles')->get()->pluck('roles')->flatten();
      })
      ->pluck('name', 'id')
      ->toArray();

    if (empty($roles)) {
      throw new AuthenticationException('U hebt geen toegangsrechten tot deze applicatie');
    }

    return $roles;
  }

}
