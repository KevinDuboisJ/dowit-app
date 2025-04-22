<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TooltipController extends Controller
{

  //HANDLE TOOLTIP TEXT FROM DB
  public function find(Request $request)
  {
    $text = 'Geen informatie beschikbaar';

    if ($request->get('name'))
      $text = DB::select('SELECT text FROM tooltips WHERE name = ?', [$request->get('name')])[0];

    return response()->json(['text' => $text]);
  }
}
