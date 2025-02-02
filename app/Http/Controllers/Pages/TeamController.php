<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
  public function search(Request $request)
  {
    $user = Auth::user();
    $userTeamsIds = $user->getTeamIds();

    $response = DB::table('teams')
      ->select('id', 'name')
      ->where(function ($query) use ($userTeamsIds) {
        $query->whereIn('id', $userTeamsIds)
          ->whereNot('id', 1); // Omit the fallback team
      })
      ->when($request->has('userInput'), function ($query) use ($request) {
        $userInput = trim(strip_tags($request->userInput)); // Sanitize the input
        $searchWords = array_filter(explode(' ', $userInput)); // Split input into words

        $query->where(function ($query) use ($searchWords, $userInput) {
          foreach ($searchWords as $word) {
            // Case-insensitive search for individual words in "name"
            $query->orWhereRaw("LOWER(name) LIKE ?", ["%" . strtolower($word) . "%"]);
          }

          // Optional: Search by the full input as a single string
          $query->orWhereRaw("LOWER(name) LIKE ?", ["%" . strtolower($userInput) . "%"]);
        });
      })
      ->get()
      ->map(function ($item) {
        return [
          'value' => $item->id,
          'label' => $item->name,
        ];
      });

    return response()->json($response);
  }
}
