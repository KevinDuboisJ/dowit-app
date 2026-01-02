<?php

namespace App\Services;

// Importing required enums, helpers, models, and Laravel utilities

use App\Enums\TaskPriorityEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Events\BroadcastEvent;
use App\Helpers\Helper;
use App\Models\Chain;
use App\Models\PATIENTLIST\Patient;
use App\Models\PATIENTLIST\Visit;
use App\Models\PATIENTLIST\Department;
use App\Models\PATIENTLIST\Room;
use App\Models\PATIENTLIST\Bed;
use App\Models\PATIENTLIST\BedVisit;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientService
{
  /**
   * Main function: Fetches occupied rooms from OAZIS, updates local DB (patientlist),
   * handles new and vacated bed visits, and updates visits.
   */
  public static function getOccupiedRooms(Chain $chain)
  {
    try {
      $now = Carbon::now(); // Current timestamp
      $resultsMap = []; // For storing formatted remote (OAZIS) bed visit results
      $inserts = []; // For collecting new bed_visit insert rows

      // SQL query to fetch all bed grid info from OAZIS
      $query = "
        SELECT TOP (1000)
        CAST(bg.CAMPUS_ID AS INT) AS campus_id,
        bg.WARD_ID       AS department_number,
        bg.ROOM_ID       AS room_number,
        bg.BED_ID        AS bed_number,
        bg.VISIT_ID      AS visit_number,
        bg.VISIT_TYPE    AS visit_type,
        bg.PAT_ID        AS patient_number,
        bg.LASTNAME      AS lastname,
        bg.FIRSTNAME     AS firstname,
        bg.SEX           AS gender,
        -- combine date + time into one column for admitted and discharged
        CAST(av.adm_date AS DATETIME) + CAST(av.adm_time AS DATETIME) AS admitted_at,
        CAST(av.dis_date AS DATETIME) + CAST(av.dis_time AS DATETIME) AS discharged_at 
        FROM OAZP.dbo.BEDGRID AS bg
        LEFT JOIN adt_visit AS av ON bg.VISIT_ID = av.visit_id
        WHERE PATINDEX('%[^0-9]%', bg.ROOM_ID) = 0 -- only numeric rooms
        ORDER BY bg.ROOM_ID;";

      // Execute query on external OAZIS DB
      $results = DB::connection('oazis')->select($query);

      // Preload departments, rooms, beds, patients, visits in memory
      $context = self::buildContextFromResults($results);

      // Load current active bed visits from local database
      $activeVisits = BedVisit::on('patientlist')
        ->with([
          'bed.room.department',
          'visit.patient',
        ])
        ->whereNull('vacated_at') // still active
        ->get()
        // Convert to key/value pairs: {visitId_bedId => BedVisit model}
        ->mapWithKeys(function ($visit) {
          return [
            self::getBedVisitKey($visit->visit_id, $visit->bed_id) => $visit,
          ];
        });

      // Process the OAZIS results
      foreach ($results as $row) {
        // Clean row (trim values, convert empty strings to null)
        $row = array_map([Helper::class, 'trimOrNull'], (array) $row);

        // Make sure referenced objects exist, create/update Visit, Patient, Room, Bed
        self::createOrUpdateVisitByContext($row, $context);

        // Resolve room, bed, visit IDs from cached context
        $row['room_id'] = $context['rooms']->get(self::getRoomKey($row['campus_id'], $row['room_number']))?->id;
        $row['bed_id'] = $context['beds']->get($row['room_id'])?->firstWhere('number', $row['bed_number'])?->id;
        $row['visit_id'] = $context['visits']->get($row['visit_number'])?->id;

        // Log potential data issues and skip invalid rows
        if (empty($row['visit_id']) || empty($row['room_id']) || empty($row['bed_id'])) {
          logger("OAZIS data inconsistency: Missing visit, room, or bed ID");
          continue;
        }

        // Add to results map with key VISITID_BEDID
        $resultsMap[self::getBedVisitKey($row['visit_id'], $row['bed_id'])] = $row;
      }

      // Determine which beds are no longer occupied
      $noLongerOccupied = $activeVisits->filter(fn($_, $key) => !isset($resultsMap[$key]));

      // Determine new bed occupancies that do not exist locally
      $newlyOccupied = collect($resultsMap)->filter(fn($_, $key) => !isset($activeVisits[$key]));

      /**
       * STEP 1: HANDLE VACATED BEDS
       */
      if ($noLongerOccupied->isNotEmpty()) {

        // Collect unique bed IDs
        $bedIds = $noLongerOccupied->pluck('bed_id')->unique();

        // Update bed_visits to assign vacated_at timestamp
        DB::connection('patientlist')->table('bed_visits')
          ->whereIn('bed_id', $bedIds)
          ->update(['vacated_at' => $now, 'updated_at' => $now]);

        // Mark beds as not occupied and not cleaned
        DB::connection('patientlist')->table('beds')
          ->whereIn('id', $bedIds)
          ->update(['occupied_at' => null, 'cleaned_at' => null, 'updated_at' => $now]);

        foreach ($noLongerOccupied as $bedVisit) {

          // Try to find matching Ultimo space ID for cleaning tasks
          $spaceId = Space::where('SpcRoomNr', $bedVisit->bed->room->number)
            ->where('campus_id', $bedVisit->bed->room->campus_id)
            ->value('id');

          if (!$spaceId) {
            // Fallback: search by name
            $spaceId = Space::where('name', 'like', '%' . $bedVisit->bed->room->number . '%')
              ->where('campus_id', $bedVisit->bed->room->campus_id)
              ->value('id');
          }

          if (!$spaceId) {
            logger("Room: {$bedVisit->bed->room->number} for visit number: {$bedVisit->visit->number} not found in Ultimo for cleaning task");
          }

          $allowedDepartments = ['2214', '3112', '2112', '3111'];

          if (in_array($bedVisit->visit->department->number, $allowedDepartments, true)) {

            $data = [
              'task' => [
                'name' => 'Eindpoets patiëntkamer',
                'start_date_time' => $now,
                'description' => "Kamer {$bedVisit->bed->room->number}, Bed {$bedVisit->bed->number} - " . strtoupper($bedVisit->visit->patient->lastname),
                'campus_id' => $bedVisit->bed->room->campus_id,
                'task_type_id' => TaskTypeEnum::EndOfStayCleaning->value,
                'space_id' => $spaceId ?? null,
                'priority' => TaskPriorityEnum::Medium->value,
                'bed_visit_id' => $bedVisit->id,
              ],
              'tags' => [],
              'assignees' => [],
              'teamsMatchingAssignment' => [],
            ];

            if ($bedVisit->bed->room->campus_id === 1) {
              $data['teamsMatchingAssignment'] = [3]; // Teams id for Antwerp
            }

            if ($bedVisit->bed->room->campus_id === 2) {
              $data['teamsMatchingAssignment'] = [6]; // Teams id for Deurne
            }

            $taskService = new TaskService();
            $task = $taskService->create($data);

            broadcast(new BroadcastEvent($task, 'task_created', $chain->identifier));
          }
        }
      }

      /**
       * STEP 2: HANDLE NEWLY OCCUPIED BEDS
       */
      foreach ($newlyOccupied as $key => $data) {

        // Log issues with missing ID references
        if (empty($data['bed_id']) || empty($data['visit_id'])) {
          Log::warning("Missing bed_id or visit_id for new visit");
        }

        // Prepare row for batch insert
        $inserts[] = [
          'bed_id'     => $data['bed_id'],
          'visit_id'   => $data['visit_id'],
          'occupied_at' => $now,
          'created_at' => $now,
          'updated_at' => $now,
        ];
      }

      // Insert new bed_visits in one batch
      if (!empty($inserts)) {
        DB::connection('patientlist')->table('bed_visits')->insert($inserts);

        // Update bed records: mark beds as occupied
        $bedIds = array_unique(array_column($inserts, 'bed_id'));
        Bed::whereIn('id', $bedIds)->update([
          'occupied_at' => $now,
        ]);
      }

      /**
       * STEP 3: FIND VISITS THAT HAVE NO ACTIVE BED VISITS BUT STILL NO DISCHARGE DATE
       */
      $visits = Visit::query()
        ->whereNull('discharged_at')  // Still active
        ->where('admitted_at', '<=', Carbon::now()->subDay(2)) // Select patients admitted at least 2 day ago to exclude those still hospitalized without an assigned room
        ->whereDoesntHave('bedVisits', function ($q) {
          $q->whereNull('vacated_at'); // No remaining active bed visits
        })
        ->pluck('number'); // Only visit numbers

      if ($visits->isNotEmpty()) {

        $results = collect(); // Placeholder for OAZIS visit details

        // Chunk to avoid large IN queries
        $visits->chunk(500)->each(function ($chunk) use (&$results) {

          // Fetch discharged_at from remote system
          $partial = DB::connection('oazis')
            ->table('OAZP.dbo.adt_visit')
            ->selectRaw("
              LTRIM(RTRIM(visit_id)) AS visit_id,
              CAST(adm_date AS DATETIME) + CAST(adm_time AS DATETIME) AS admitted_at,
              CAST(dis_date AS DATETIME) + CAST(dis_time AS DATETIME) AS discharged_at
            ")
            ->whereIn('visit_id', $chunk)
            ->get();

          // Detect duplicate visit numbers (should never happen)
          $duplicateVisits = $partial
            ->groupBy('visit_id')
            ->filter(fn($rows) => $rows->count() > 1)
            ->keys();

          if ($duplicateVisits->isNotEmpty()) {
            throw new \Exception("Duplicate visit records detected for: " . $duplicateVisits->join(', '));
          }

          // Add results
          $results = $results->merge($partial);
        });

        // Update visits locally with discharge date
        foreach ($results as $row) {

          if (!$row->discharged_at) {
            logger("Visit {$row->visit_id} has no discharge date and no active bed");
          }

          Visit::where('number', $row->visit_id)
            ->update([
              'campus_id'     => null,
              'department_id' => null,
              'bed_id'        => null,
              'discharged_at' => $row->discharged_at,
            ]);
        }
      }
    } catch (\Throwable $e) {

      // Log any exception details for debugging
      Log::debug([
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
      ]);
    }
  }

  /**
   * Utility to create consistent key for bed visit lookups
   */
  public static function getBedVisitKey($visitId, $bedId)
  {
    return "{$visitId}_{$bedId}";
  }

  /**
   * Utility to create consistent key for room lookup
   */
  public static function getRoomKey($campusId, $roomNumber)
  {
    return $campusId . '_' . $roomNumber;
  }

  /**
   * Creates or updates Department, Room, Bed, Patient, and Visit
   * using preloaded context to minimize database queries.
   */
  public static function createOrUpdateVisitByContext($data, &$context)
  {
    // DEPARTMENT HANDLING ---------------------------------------------------

    $department = $context['departments']->get($data['department_number']);

    // Create new department if not found
    if (!$department) {
      $department = Department::create(['number' => $data['department_number']]);
      $context['departments'][$data['department_number']] = $department;
    }

    // ROOM HANDLING ---------------------------------------------------------

    $roomKey = self::getRoomKey($data['campus_id'], $data['room_number']);
    $room = $context['rooms']->get($roomKey);

    if (!$room) {
      // Create new room
      $room = Room::create([
        'number' => $data['room_number'],
        'department_id' => $department->id,
        'campus_id' => $data['campus_id'],
      ]);
      $context['rooms'][$roomKey] = $room;
    } elseif ($room->department_id !== $department->id) {
      // Room moved to a different department (rare case)
      $room->update(['department_id' => $department->id]);
      $context['rooms'][$roomKey] = $room;
    }

    // BED HANDLING ----------------------------------------------------------

    $bed = $context['beds'][$room->id][$data['bed_number']] ?? null;

    if (!$bed) {
      // Create bed if not exists
      $bed = Bed::create([
        'number' => $data['bed_number'],
        'room_id' => $room->id,
      ]);

      // Add to context cache
      if (!isset($context['beds'][$room->id])) {
        $context['beds'][$room->id] = collect();
      }

      $context['beds'][$room->id]->put($data['bed_number'], $bed);
    }

    // PATIENT HANDLING ------------------------------------------------------

    $patient = $context['patients']->get($data['patient_number'])
      ?? new Patient(['number' => $data['patient_number']]);

    // Update patient fields
    $patient->fill([
      'firstname' => $data['firstname'],
      'lastname'  => $data['lastname'],
      'gender'    => self::formatOazisGender($data['gender']),
    ]);

    // Save only if modified
    if ($patient->isDirty()) {
      $patient->save();
    }

    $context['patients'][$patient->number] = $patient;

    // VISIT HANDLING --------------------------------------------------------

    $visit = $context['visits']->get($data['visit_number'])
      ?? new Visit(['number' => $data['visit_number']]);

    // Update visit information
    $visit->fill([
      'patient_id'    => $patient->id,
      'campus_id'     => $data['campus_id'],
      'department_id' => $department->id,
      'bed_id'        => $bed->id,
      'admitted_at'   => $data['admitted_at'] ?? $visit->admitted_at,
      'discharged_at' => $data['discharged_at'] ?? $visit->discharged_at,
    ]);

    if ($visit->isDirty()) {
      $visit->save();
    }

    $context['visits'][$visit->number] = $visit;
  }

  /**
   * Preloads db tables rows to avoid repeated database queries (speed optimization)
   */
  public static function buildContextFromResults(array $rows)
  {
    $rows = collect($rows)->map(fn($r) => (array) $r);

    $departmentNumbers = $rows->pluck('department_number')->filter()->unique();
    $patientNumbers    = $rows->pluck('patient_number')->filter()->unique();
    $visitNumbers      = $rows->pluck('visit_number')->filter()->unique();

    // Rooms identified by campus + room_number
    $roomKeys = $rows->map(function ($row) {
      return self::getRoomKey($row['campus_id'], $row['room_number']);
    })->unique();

    $departments = Department::whereIn('number', $departmentNumbers)->get()->keyBy('number');

    $rooms = Room::whereIn(DB::raw("CONCAT(campus_id, '_', number)"), $roomKeys->all())
      ->get()
      ->keyBy(fn($room) => self::getRoomKey($room->campus_id, $room->number));

    $beds = Bed::whereIn('room_id', $rooms->pluck('id'))
      ->get()
      ->groupBy('room_id')
      ->map(fn($group) => $group->keyBy('number'));

    $patients = Patient::whereIn('number', $patientNumbers)->get()->keyBy('number');
    $visits   = Visit::whereIn('number', $visitNumbers)->get()->keyBy('number');

    return compact('departments', 'rooms', 'beds', 'patients', 'visits');
  }


  /**
   * When a final cleaning task is completed, mark the bed or bed visit as cleaned.
   */
  public static function handleFinalCleanTask(Task $task): void
  {
    if ($task->status_id === TaskStatusEnum::Completed->value && $task->bed_visit_id) {

      $taskBedVisit = $task->bedVisit;

      if ($taskBedVisit) {
        $now = now();

        // Update cleaning timestamp on bed_visit row
        $taskBedVisit->update(['cleaned_at' => $now]);

        // Check if bed got used again after this cleaning task
        $hasNewerVisit = BedVisit::where('bed_id', $taskBedVisit->bed_id)
          ->where('occupied_at', '>', $taskBedVisit->occupied_at)
          ->exists();

        // If no newer visits, mark bed as cleaned
        if (!$hasNewerVisit) {
          $taskBedVisit->bed->update(['cleaned_at' => now()]);
        }
      } else {
        logger('handleFinalCleanTask: No bed visit found for Task #' . $task->id);
      }
    }
  }

  // Helper formatting functions for OAZIS data

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