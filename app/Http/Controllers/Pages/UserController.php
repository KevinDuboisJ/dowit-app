<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
  public function search(Request $request)
  {
    $userTeams = auth()->user()->teams; // Assuming the user's teams are fetched here
    $userInput = trim(strip_tags($request->userInput)); // Sanitize the input
    $searchWords = array_filter(explode(' ', $userInput)); // Split input into words

    $users = User::with(['teams' => function ($query) use ($userTeams) {
      $query->whereIn('teams.id', $userTeams->pluck('id'));
    }])
      ->excludeSystemUser()
      ->where(function ($query) use ($searchWords, $userInput) {
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
