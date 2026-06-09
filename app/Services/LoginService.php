<?php

namespace App\Services;

use App\Contracts\SupportsImpersonation;
use Illuminate\Http\Request;
use App\Contracts\UserAuthenticator;
use App\Enums\EventEnum;
use App\Models\Comment;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use App\Traits\HasImpersonation;
use App\Models\EDB\Role as EDBRole;
use App\Services\LdapService;
use App\Models\Role;

class LoginService implements UserAuthenticator
{
  use HasImpersonation;

  protected LdapService $ldapService;

  public function __construct(LdapService $ldapService)
  {
    $this->ldapService = $ldapService;
  }

  public function authenticate(string $username, string $password): User
  {
    $this->resolveImpersonate($username);
    $primaryUsername = $this->getPrimaryUsername();
    $loginAsUsername = $this->getLoginAsUsername();
    $this->ldapService->bind($primaryUsername, $password);

    $user = User::where('username', $loginAsUsername)->where('is_active', true)->firstOrFail();

    // Retrieve Dowit account roles from EDB
    $roles = EDBRole::byDowitAccountUsername($loginAsUsername)
      ->pluck('ro_name')
      // Get role names as keys and role ids as values
      ->toArray();

    $roles = Role::whereIn('code', $roles)
      ->pluck('code', 'id') // Get role id as key and role name as value
      ->toArray();

    if (empty($roles)) {
      throw new AuthenticationException('Je hebt geen toegangsprofiel');
    }

    session(['roles' => $roles]);

    if (!$this->isImpersonated()) {
      // Record the timestamp of the user's last login attempt to monitor login activity.
      $user->setLastLogin();
      $user->save();
      Comment::create([
        'created_by' => $user->id,
        'event' => EventEnum::UserLoggedIn->value,
        'content' => 'Gebruiker heeft zich ingelogd',
      ]);

      $device = Device::resolveFromHostname();
      $device->setLastUsed($user)->save();
      $device->logUserUsage($user, EventEnum::UserLoggedIn);
    }



    Auth::login($user);
    return $user;
  }

  public function validate(Request $request): array
  {
    return $request->validate([
      'username' => ['required'],
      'password' => ['required', 'min:8'],
    ]);
  }
}
