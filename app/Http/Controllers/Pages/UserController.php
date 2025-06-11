<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
  public function search(Request $request)
  {
    $searchWords = array_filter(explode(' ', trim(strip_tags($request->userInput)))); // Split input into words

    $users = User::byTeams()
      ->excludeSystemUser()
      ->where(function ($query) use ($searchWords) {
        foreach ($searchWords as $word) {
          // Case-insensitive search for firstname and lastname
          $query->orWhereRaw("LOWER(firstname) LIKE ?", ["%" . strtolower($word) . "%"])
            ->orWhereRaw("LOWER(lastname) LIKE ?", ["%" . strtolower($word) . "%"]);
        }
      })
      ->get()
      ->map(function ($user) {
        return [
          'value' => $user->id,
          'label' => "{$user->firstname} {$user->lastname}",
        ];
      });

    return response()->json($users);
  }
}
