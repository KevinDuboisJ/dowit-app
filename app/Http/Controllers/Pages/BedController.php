<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\PATIENTLIST\Bed;
use App\Models\PATIENTLIST\Department;

class BedController extends Controller
{
  public function index()
  {
    return Inertia::render('Bed', [
      'beds' => Bed::with([
        'room',
        'room.campus',
        'room.department',
        'bedVisits.visit.patient' => fn($q) => $q->select('id', 'number', 'firstname', 'lastname'),
        'bedVisits' => fn($q) => $q->latest()->take(6),
      ])
        ->withCount([
          'bedVisits as current_visit' => fn($q) => $q->whereNull('vacated_at'),
        ])
        ->get(),

      'departments' => Department::select('number')->distinct()->orderBy('number')->get(),
    ]);
  }
}
