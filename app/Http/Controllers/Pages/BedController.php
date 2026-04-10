<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\PATIENTLIST\Bed;
use App\Models\PATIENTLIST\BedVisit;
use App\Models\PATIENTLIST\Department;
use Illuminate\Http\Request;

class BedController extends Controller
{
  public function index(Request $request)
  {
    $filters = [
      'department' => $request->string('department')->value() ?: 'all',
      'campus' => $request->string('campus')->value() ?: 'all',
      'room' => $request->string('room')->value() ?: 'all',
      'bed' => $request->string('bed')->value() ?: 'all',
      'room_type' => $request->string('room_type')->value() ?: 'all',
      'needs_cleaning_only' => $request->boolean('needs_cleaning_only'),
      'show_occupied_only' => $request->boolean('show_occupied_only'),
      'show_cleaned_only' => $request->boolean('show_cleaned_only'),
    ];

    $departmentIds = ['2214', '3112', '2112', '3111'];

    $beds = Bed::query()
      ->with([
        'room.campus',
        'room.department',
        'bedVisits.visit.patient',
      ])
      ->whereHas('room.department', function ($q) use ($departmentIds) {
        $q->whereIn('number', $departmentIds);
      })
      ->filter($filters)
      ->latest('id')
      ->paginate(25)
      ->withQueryString()
      ->through(function (Bed $bed) {
        return [
          'id' => $bed->id,
          'number' => $bed->number,
          'occupied_at' => $bed->occupied_at,
          'cleaned_at' => $bed->cleaned_at,
          'room' => [
            'number' => $bed->room?->number,
            'campus' => [
              'name' => $bed->room?->campus?->name,
            ],
            'department' => [
              'number' => $bed->room?->department?->number,
            ],
          ],
          'bed_visits' => $bed->bedVisits->map(function ($bedVisit) {
            return [
              'id' => $bedVisit->id,
              'vacated_at' => $bedVisit->vacated_at,
              'occupied_at' => $bedVisit->occupied_at,
              'visit' => [
                'patient' => [
                  'firstname' => $bedVisit->visit?->patient?->firstname,
                  'lastname' => $bedVisit->visit?->patient?->lastname,
                ],
              ],
            ];
          }),
        ];
      });

    $filterOptions = [
      'departments' => Department::query()
        ->orderBy('number')
        ->pluck('number')
        ->values(),
      'campuses' => Bed::query()
        ->join('rooms', 'rooms.id', '=', 'beds.room_id')
        ->join('campuses', 'campuses.id', '=', 'rooms.campus_id')
        ->whereNotNull('campuses.name')
        ->distinct()
        ->orderBy('campuses.name')
        ->pluck('campuses.name')
        ->values(),
      'rooms' => Bed::query()
        ->join('rooms', 'rooms.id', '=', 'beds.room_id')
        ->whereNotNull('rooms.number')
        ->distinct()
        ->orderByRaw('CAST(rooms.number as unsigned)')
        ->pluck('rooms.number')
        ->values(),
      'beds' => Bed::query()
        ->whereNotNull('number')
        ->distinct()
        ->orderByRaw('CAST(number as unsigned)')
        ->pluck('number')
        ->values(),
    ];

    return Inertia::render('Bed', [
      'beds' => $beds,
      'filters' => $filters,
      'filterOptions' => $filterOptions,
    ]);
  }
}
