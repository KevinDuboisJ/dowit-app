<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tag;

class TagController extends Controller
{
  public function search(Request $request)
  {
    $response = Tag::select('id', 'name')
    ->byUserInput($request->input('userInput'))
    ->limit(15)
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
