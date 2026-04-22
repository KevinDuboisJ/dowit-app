<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PATIENTLIST\Bed;
use App\Models\PATIENTLIST\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BedController extends Controller
{
  public function index(Request $request): Response
  {
    $filters = $this->getFilters($request);

    $beds = Bed::query()
      ->with([
        'room.campus',
        'room.department',
        'bedVisits.visit.patient',
        'latestBedVisit',
      ])
      ->filter($filters)
      ->latest('id')
      ->paginate(25)
      ->withQueryString()
      ->through(fn(Bed $bed) => $this->transformBed($bed));

    $filterOptions = $this->getFilterOptions();

    return Inertia::render('Bed', [
      'beds' => $beds,
      'filters' => $filters,
      'filterOptions' => $filterOptions,
    ]);
  }

  private function getFilters(Request $request): array
  {
    return [
      'department' => $request->string('department')->value() ?: 'all',
      'campus' => $request->string('campus')->value() ?: 'all',
      'room' => $request->string('room')->value() ?: 'all',
      'bed' => $request->string('bed')->value() ?: 'all',
      'room_type' => $request->string('room_type')->value() ?: 'all',
      'needs_cleaning_only' => $request->boolean('needs_cleaning_only'),
      'show_occupied_only' => $request->boolean('show_occupied_only'),
      'show_cleaned_only' => $request->boolean('show_cleaned_only'),
    ];
  }

  private function transformBed(Bed $bed): array
  {
    $room = $bed->room;
    $latestBedVisit = $bed->latestBedVisit;

    return [
      'id' => $bed->id,
      'number' => $bed->number,
      'room' => [
        'number' => $room?->number,
        'campus' => [
          'name' => $room?->campus?->name,
        ],
        'department' => [
          'number' => $room?->department?->number,
        ],
      ],
      'bed_visits' => $bed->bedVisits->map(fn($bedVisit) => $this->transformBedVisit($bedVisit)),
      'latest_bed_visit' => $latestBedVisit ? [
        'id' => $latestBedVisit->id,
        'occupied_at' => $latestBedVisit->occupied_at,
        'vacated_at' => $latestBedVisit->vacated_at,
        'cleaned_at' => $latestBedVisit->cleaned_at,
      ] : null,
    ];
  }

  private function transformBedVisit($bedVisit): array
  {
    $patient = $bedVisit->visit?->patient;

    return [
      'id' => $bedVisit->id,
      'vacated_at' => $bedVisit->vacated_at,
      'occupied_at' => $bedVisit->occupied_at,
      'visit' => [
        'patient' => [
          'firstname' => $patient?->firstname,
          'lastname' => $patient?->lastname,
        ],
      ],
    ];
  }

  private function getFilterOptions(): array
  {
    return [
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
  }
}
