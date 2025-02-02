<?php

namespace App\Contracts;

use Illuminate\Http\Request;
use App\Models\User;

interface UserAuthenticator
{
  // Authenticate a user based on the provided username and password.
  public function authenticate(string $username, string $password): User;
  public function validate(Request $request): array;
}
