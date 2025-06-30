<?php

namespace App\Services;

use App\Models\PATIENTLIST\Patient;
use App\Models\PATIENTLIST\Visit;
use App\Models\PATIENTLIST\Department;
use App\Models\PATIENTLIST\Room;
use App\Models\PATIENTLIST\Bed;
use App\Models\Space;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class PatientService
{
  public static function createOrUpdateVisit($data)
  {
    $spaceId = Space::where('SpcRoomNr', $data['room_number'])->where('campus_id', $data['campus_id'])->value('id'); // Fast with index

    if (!$spaceId) {
      $spaceId = Space::where('name', 'like', '%' . $data['room_number'] . '%')->where('campus_id', $data['campus_id'])->value('id'); // Slower fallback
    }

    if (!$spaceId) {
      logger("Kamer niet gevonden in Ultimo: Eindpoets patiëntkamer Kamer {$data['room_number']}, Bed {$data['bed_number']} - " . strtoupper($data['lastname']));
    }

    $department = Department::firstOrCreate(
      ['number' => $data['department_number']],
      ['number' => $data['department_number']]
    );

    $room = Room::firstOrCreate(
      ['number' => $data['room_number']],
      ['number' => $data['room_number']]
    );

    $bed = Bed::firstOrCreate(
      ['number' => $data['bed_number'], 'room_id' => $room->id],
      ['number' => $data['bed_number'], 'room_id' => $room->id, 'occupied' => Carbon::now(), 'cleaned' => null]
    );

    // Create or Update the Patient
    $patient = Patient::updateOrCreate(
      ['number'  => $data['patient_number']], // Find by API patient ID
      [
        'number' => $data['patient_number'],
        'firstname'      => $data['firstname'],
        'lastname'       => $data['lastname'],
        'gender'         => self::formatOazisGender($data['gender']),
      ]
    );

    $existingVisit = Visit::where('number', $data['visit_number'])->first();

    return Visit::updateOrCreate(
      ['number' => $data['visit_number']],
      [
        'patient_id'    => $patient->id,
        'space_id'      => $spaceId ?? null,
        'campus_id'     => $data['campus_id'],
        'department_id' => $department->id,
        'room_id'       => $room->id,
        'bed_id'        => $bed->id,
        'admission'     => !empty($data['admission']) ? $data['admission'] : ($existingVisit?->admission ?? now()),
        'discharge'     => $data['discharge'] ?? null,
      ]
    );
  }

  public static function createOrUpdateVisitByContext($data, $context)
  {
    $spaceId = optional(optional($context['spaces'][$data['campus_id']] ?? collect())->get($data['room_number']))->id
      ?? optional(optional($context['spacesByName'][$data['campus_id']] ?? collect())->get($data['room_number']))->id;

    if (!$spaceId) {
      logger("Kamer niet gevonden in Ultimo: Eindpoets patiëntkamer Kamer {$data['room_number']}, Bed {$data['bed_number']} - " . strtoupper($data['lastname']));
    }
  
    $department = $context['departments']->get($data['department_number']) ?? Department::create(['number' => $data['department_number']]);

    $room = $context['rooms']->get($data['room_number']) ?? Room::create(['number' => $data['room_number']]);
    
    $bed = optional($context['beds'][$room->id] ?? collect())->get($data['bed_number']) ?? Bed::create(['number' => $data['bed_number'], 'room_id' => $room->id, 'occupied' => Carbon::now(), 'cleaned' => null]);

    $patient = $context['patients']->get($data['patient_number']);

    if (!$patient) {
      $patient = new Patient(['number' => $data['patient_number']]);
    }

    $patient->fill([
      'firstname' => $data['firstname'],
      'lastname'  => $data['lastname'],
      'gender'    => self::formatOazisGender($data['gender']),
    ]);

    if ($patient->isDirty()) {
      $patient->save();
    }

    $visit = $context['visits']->get($data['visit_number']) ?? new Visit(['number' => $data['visit_number']]);

    $visit->fill([
      'patient_id'    => $patient->id,
      'space_id'      => $spaceId,
      'campus_id'     => (int) $data['campus_id'],
      'department_id' => $department->id,
      'room_id'       => $room->id,
      'bed_id'        => $bed->id,
      'admission'     => $data['admission'] ?? $visit->admission ?? now(),
      'discharge'     => $data['discharge'] ?? null,
    ]);

    if ($visit->isDirty()) {
       $dirty = $visit->getDirty();
      // dd('original', $visit, 'Changed values:', $dirty);
      $visit->save();
    }
  }

  public static function getOccupiedRooms(): null|object
  {
    try {
      $context = self::preloadTables();

      $query = "
    SELECT
      CAMPUS_ID as campus_id
      ,WARD_ID as department_number
      ,ROOM_ID as room_number
      ,BED_ID as bed_number
      ,VISIT_ID as visit_number
      ,VISIT_TYPE as visit_type
      ,PAT_ID as patient_number
      ,LASTNAME as lastname
      ,FIRSTNAME as firstname
      ,SEX as gender
      FROM OAZP.dbo.BEDGRID
      WHERE PATINDEX('%[^0-9]%', ROOM_ID) = 0
      ORDER BY ROOM_ID";

      $results = DB::connection('oazis')->select($query);
      logger($results);
      $currentOccupied = [];

      foreach ($results as $row) {
        $row = array_map('trim', (array) $row);
        self::createOrUpdateVisitByContext($row, $context);
        $currentOccupied[] = $row;
      }

      self::syncBedVisits($currentOccupied);

      return null;
    } catch (\Throwable $e) {
      dd([
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
      ]);
    }
  }

  public static function syncBedVisits(array $currentOccupied): void
  {
    $inserts = [];
    $updates = [];
    $now = Carbon::now();
    $allRooms = Room::get()->keyBy('number');
    $allBeds = Bed::get()->groupBy('room_id');
    $allVisits = Visit::get()->keyBy('number');

    // Step 1: Fetch existing active bed_visits
    $activeVisits = DB::connection('patientlist')->table('bed_visits')
      ->whereNull('stop_date')
      ->get()
      ->keyBy(fn($v) => $v->bed_id . '_' . $v->visit_id);

    // Step 2: Build current map of occupied beds
    $currentMap = collect($currentOccupied)->mapWithKeys(function ($row) use ($allRooms, $allBeds, $allVisits, $now) {
      $room = $allRooms->get($row['room_number']);
      $bed = $allBeds->get($room?->id)?->firstWhere('number', $row['bed_number']);
      $visit = $allVisits->get($row['visit_number']);

      return [$bed?->id . '_' . $visit?->id => [
        'bed_id' => $bed?->id,
        'visit_id' => $visit?->id,
        'start_date' => $now,
      ]];
    });

    // Step 3: Close visits that are no longer present
    foreach ($activeVisits as $key => $visit) {
      if (!isset($currentMap[$key])) {
        $updates[] = $visit->id;
      }
    }

    if (!empty($updates)) {
      DB::connection('patientlist')->table('bed_visits')
        ->whereIn('id', $updates)
        ->update(['stop_date' => $now]);
    }

    // Step 4: Insert new visits
    foreach ($currentMap as $key => $data) {
      if (!isset($activeVisits[$key]) && $data['bed_id'] && $data['visit_id']) {
        $inserts[] = [
          'bed_id'     => $data['bed_id'],
          'visit_id'   => $data['visit_id'],
          'start_date' => $now,
          'created_at' => $now,
          'updated_at' => $now,
        ];
      }
    }

    if (!empty($inserts)) {
      DB::connection('patientlist')->table('bed_visits')->insert($inserts);

      $bedIds = array_unique(array_column($inserts, 'bed_id'));

      Bed::whereIn('id', $bedIds)
        ->update([
          'cleaned' => null,
          'occupied' => Carbon::now(),
        ]);
    }
  }

  private static function preloadTables()
  {
    $departments = Department::all()->keyBy('number');
    $rooms = Room::all()->keyBy('number');
    $beds = Bed::all()->groupBy('room_id')->map(function ($group) {
      return $group->keyBy('number');
    });
    
    $patients = Patient::all()->keyBy('number');
    $visits = Visit::all()->keyBy('number');

    return ['departments' => $departments, 'rooms' => $rooms, 'beds' => $beds, 'departments' => $departments, 'patients' => $patients, 'visits' => $visits];
  }

  public static function formatOazisBirthdate(string|null $birthdate)
  {
    if ($birthdate) {
      return Carbon::parse($birthdate)->format('d-m-Y');
    }
    return null;
  }

  public static function formatOazisGender(string|null $gender)
  {
    if ($gender == '1' || strtoupper($gender) == 'M')
      return 'M';
    if ($gender == '2' || strtoupper($gender) == 'V')
      return 'V';

    return '';
  }

  public static function formatOazisCampus(string|null $campus)
  {
    if ($campus == '002')
      return 'Deurne';
    if ($campus == '001')
      return 'Antwerpen';

    return '';
  }
}
