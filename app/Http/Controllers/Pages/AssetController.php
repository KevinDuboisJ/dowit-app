<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Asset;

class AssetController extends Controller
{
  public function index(Request $request)
  {
    $assets = Asset::query()
      ->when($request->filled('search'), function ($query) use ($request) {
        $search = $request->input('search');
        $query->where('name', 'like', '%' . $search . '%');
      })
      ->paginate()
      ->withQueryString();

    return Inertia::render('Asset', [
      'assets' => $assets,
    ]);
  }
}
