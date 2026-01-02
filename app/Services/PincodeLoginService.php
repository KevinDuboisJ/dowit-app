<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Contracts\UserAuthenticator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use App\Models\EDB\Role as EDBRole;
use App\Models\Role;

class PincodeLoginService implements UserAuthenticator
{

  public function authenticate(string $username, string $password): User
  {
    $user = User::where('username', $username)
      ->where('is_active', true)
      ->firstOrFail();

    if (!Hash::check($password, $user->password)) {
      throw new AuthenticationException('PIN code is onjuist');
    }

    // Retrieve Dowit account roles from EDB
    $roles = EDBRole::byDowitAccountUsername($username)
      ->pluck('ro_name')
      // Get role names as keys and role ids as values
      ->toArray();

    $roles = Role::whereIn('code', $roles)
      ->pluck('code', 'id') // Get role id as key and role name as value
      ->toArray();

    if (empty($roles)) {
      throw new AuthenticationException('Je hebt geen toegangsprofiel');
    }

    $user->roles = $roles;

    Auth::login($user);
    return $user;
  }

  public function validate(Request $request): array
  {
    return $request->validate([
      'username' => ['required'],
      'password' => ['required', 'digits_between: 3,10'],
    ]);
  }
}
