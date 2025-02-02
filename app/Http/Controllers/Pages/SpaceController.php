<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Space;

class SpaceController extends Controller
{
  public function search(Request $request)
  {
    $response = Space::select('id', 'name')
    ->byUserInput($request->input('userInput'))
    ->limit(15)
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
