<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Space;

class SpaceController extends Controller
{
  public function search(Request $request)
  {
    if (strlen($request->input('userInput')) < 2) {
      return response()->json([]); // or some default/fallback if needed
    }

    $response = Space::select('id', 'name')
      ->byUserInput($request->input('userInput'))
      ->limit(45)
      ->get()
      ->map(function ($space) {
        return [
          'value' => $space->id,
          'label' => $space->name,
        ];
      });

    return response()->json($response);
  }
}
