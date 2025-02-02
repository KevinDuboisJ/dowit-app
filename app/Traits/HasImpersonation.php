<?php

namespace App\Traits;

trait HasImpersonation
{
  protected string $primaryUsername;
  protected string $loginAsUsername;

  protected function resolveImpersonate(string $username): void
  {
    $this->primaryUsername = $username;
    $this->loginAsUsername = $username;

    // Check if the username contains a impersonation
    if ($this->canImpersonate($username) && strpos($username, '@') !== false) {
      [$this->primaryUsername, $this->loginAsUsername] = explode('@', $username);
    }
  }

  protected function isImpersonated(): bool
  {
    return $this->primaryUsername != $this->loginAsUsername;
  }

  protected function canImpersonate(string $username): bool
  {
    // Retrieve usernames of persons that can impersonate
    $admins = array_map('strtolower', explode(',', config('app.admins')));

    // Check if the username contains a impersonation.
    return in_array(explode('@', strtolower($username))[0], $admins);
  }

  protected function getPrimaryUsername(): string
  {
    return $this->primaryUsername;
  }

  protected function getLoginAsUsername(): string
  {
    return $this->loginAsUsername;
  }
}
