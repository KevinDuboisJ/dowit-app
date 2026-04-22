<?php

namespace App\Services;

use App\Enums\TaskPriorityEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Events\BroadcastEvent;
use App\Helpers\Helper;
use App\Models\PATIENTLIST\Bed;
use App\Models\PATIENTLIST\BedVisit;
use App\Models\PATIENTLIST\Department;
use App\Models\PATIENTLIST\Patient;
use App\Models\PATIENTLIST\Room;
use App\Models\PATIENTLIST\Visit;
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
  public static function getOccupiedRooms()
  {
    try {
      $now = Carbon::now();
      $resultsMap = [];
      $inserts = [];

      $results = DB::connection('oazis')->select(self::getOccupiedRoomsQuery());

      $context = self::buildContextFromResults($results);
      $activeVisits = self::getActiveBedVisits();

      foreach ($results as $row) {
        $row = self::normalizeOccupiedRoomRow($row);

        self::createOrUpdateVisitByContext($row, $context);

        $row = self::attachResolvedIdsToRow($row, $context);

        if (self::hasMissingRequiredIds($row)) {
          logger("OAZIS data inconsistency: Missing visit, room, or bed ID");
          continue;
        }

        $resultsMap[self::getBedVisitKey($row['visit_id'], $row['bed_id'])] = $row;
      }

      $noLongerOccupied = $activeVisits->filter(fn($_, $key) => !isset($resultsMap[$key]));
      $newlyOccupied = collect($resultsMap)->filter(fn($_, $key) => !isset($activeVisits[$key]));

      self::handleVacatedBeds($noLongerOccupied, $now);

      foreach ($newlyOccupied as $key => $data) {
        if (empty($data['bed_id']) || empty($data['visit_id'])) {
          Log::warning("Missing bed_id or visit_id for new visit");
        }

        $inserts[] = [
          'bed_id' => $data['bed_id'],
          'visit_id' => $data['visit_id'],
          'occupied_at' => $now,
          'created_at' => $now,
          'updated_at' => $now,
        ];
      }

      if (!empty($inserts)) {
        DB::connection('patientlist')->table('bed_visits')->insert($inserts);

        $bedIds = array_unique(array_column($inserts, 'bed_id'));

        Bed::whereIn('id', $bedIds)->update([
          'occupied_at' => $now,
        ]);
      }

      self::handleVisitsWithoutActiveBeds();
    } catch (\Throwable $e) {
      Log::debug([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
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
    $department = $context['departments']->get($data['department_number']);

    if (!$department) {
      $department = Department::create([
        'number' => $data['department_number'],
      ]);

      $context['departments'][$data['department_number']] = $department;
    }

    $roomKey = self::getRoomKey($data['campus_id'], $data['room_number']);
    $room = $context['rooms']->get($roomKey);

    if (!$room) {
      $room = Room::create([
        'number' => $data['room_number'],
        'department_id' => $department->id,
        'campus_id' => $data['campus_id'],
      ]);

      $context['rooms'][$roomKey] = $room;
    } elseif ($room->department_id !== $department->id) {
      $room->update([
        'department_id' => $department->id,
      ]);

      $context['rooms'][$roomKey] = $room;
    }

    $bed = $context['beds'][$room->id][$data['bed_number']] ?? null;

    if (!$bed) {
      $bed = Bed::create([
        'number' => $data['bed_number'],
        'room_id' => $room->id,
      ]);

      if (!isset($context['beds'][$room->id])) {
        $context['beds'][$room->id] = collect();
      }

      $context['beds'][$room->id]->put($data['bed_number'], $bed);
    }

    $patient = $context['patients']->get($data['patient_number'])
      ?? new Patient(['number' => $data['patient_number']]);

    $patient->fill([
      'firstname' => $data['firstname'],
      'lastname' => $data['lastname'],
      'gender' => self::formatOazisGender($data['gender']),
    ]);

    if ($patient->isDirty()) {
      $patient->save();
    }

    $context['patients'][$patient->number] = $patient;

    $visit = $context['visits']->get($data['visit_number'])
      ?? new Visit(['number' => $data['visit_number']]);

    $prevBedId = $visit->bed_id;

    $visit->fill([
      'patient_id' => $patient->id,
      'campus_id' => $data['campus_id'],
      'department_id' => $department->id,
      'bed_id' => $bed->id,
      'admitted_at' => $data['admitted_at'] ?? $visit->admitted_at,
      'discharged_at' => $data['discharged_at'] ?? $visit->discharged_at,
    ]);

    if ($visit->isDirty()) {
      $visit->save();

      if ($prevBedId && $prevBedId !== $bed->id) {
        $patientTransportTaskTypes = [
          TaskTypeEnum::PatientTransportInBed->value,
          TaskTypeEnum::PatientTransportInWheelchair->value,
          TaskTypeEnum::PatientTransportOnFootAssisted->value,
          TaskTypeEnum::PatientTransportNotify->value,
          TaskTypeEnum::PatientTransportWithCrutches->value,
        ];

        $spaceId = self::resolveSpaceIdFromRoom($room, $visit->number);

        if ($prevBedId && $prevBedId !== $bed->id) {

          $spaceId = self::resolveSpaceIdFromRoom($room, $visit->number);

          Task::where('visit_id', $visit->id)
            ->whereIn('task_type_id', $patientTransportTaskTypes)
            ->where('status_id', '!=', TaskStatusEnum::Completed->value)
            ->update([
              'space_id' => $spaceId,
              'updated_at' => now(),
            ]);
        }
      }
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
    $patientNumbers = $rows->pluck('patient_number')->filter()->unique();
    $visitNumbers = $rows->pluck('visit_number')->filter()->unique();

    $roomKeys = $rows->map(function ($row) {
      return self::getRoomKey($row['campus_id'], $row['room_number']);
    })->unique();

    $departments = Department::whereIn('number', $departmentNumbers)
      ->get()
      ->keyBy('number');

    $rooms = Room::whereIn(DB::raw("CONCAT(campus_id, '_', number)"), $roomKeys->all())
      ->get()
      ->keyBy(fn($room) => self::getRoomKey($room->campus_id, $room->number));

    $beds = Bed::whereIn('room_id', $rooms->pluck('id'))
      ->get()
      ->groupBy('room_id')
      ->map(fn($group) => $group->keyBy('number'));

    $patients = Patient::whereIn('number', $patientNumbers)->get()->keyBy('number');
    $visits = Visit::whereIn('number', $visitNumbers)->get()->keyBy('number');

    return compact('departments', 'rooms', 'beds', 'patients', 'visits');
  }

  /**
   * When a final cleaning task is completed, mark the bed or bed visit as cleaned.
   */
  public static function handleFinalCleanTask(Task $task): void
  {
    if (
      $task->status_id === TaskStatusEnum::Completed->value
      && $task->bed_visit_id
      && $task->task_type_id === TaskTypeEnum::EndOfStayCleaning->value
    ) {
      $taskBedVisit = $task->bedVisit;

      if ($taskBedVisit) {
        $now = now();

        $taskBedVisit->update([
          'cleaned_at' => $now,
        ]);

        $hasNewerVisit = BedVisit::where('bed_id', $taskBedVisit->bed_id)
          ->where('occupied_at', '>', $taskBedVisit->occupied_at)
          ->exists();

        if (!$hasNewerVisit) {
          $taskBedVisit->bed->update([
            'cleaned_at' => now(),
          ]);
        }
      } else {
        logger('handleFinalCleanTask: No bed visit found for Task #' . $task->id);
      }
    }
  }

  private static function getOccupiedRoomsQuery(): string
  {
    return "
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
      CAST(av.adm_date AS DATETIME) + CAST(av.adm_time AS DATETIME) AS admitted_at,
      CAST(av.dis_date AS DATETIME) + CAST(av.dis_time AS DATETIME) AS discharged_at 
      FROM OAZP.dbo.BEDGRID AS bg
      LEFT JOIN adt_visit AS av ON bg.VISIT_ID = av.visit_id
      WHERE PATINDEX('%[^0-9]%', bg.ROOM_ID) = 0
      ORDER BY bg.ROOM_ID;
    ";
  }

  private static function getActiveBedVisits()
  {
    return BedVisit::on('patientlist')
      ->with([
        'bed.room.department',
        'visit.patient',
      ])
      ->whereNull('vacated_at')
      ->get()
      ->mapWithKeys(function ($visit) {
        return [
          self::getBedVisitKey($visit->visit_id, $visit->bed_id) => $visit,
        ];
      });
  }

  private static function normalizeOccupiedRoomRow($row): array
  {
    return array_map([Helper::class, 'trimOrNull'], (array) $row);
  }

  private static function attachResolvedIdsToRow(array $row, array $context): array
  {
    $row['room_id'] = $context['rooms']->get(
      self::getRoomKey($row['campus_id'], $row['room_number'])
    )?->id;

    $row['bed_id'] = $context['beds']->get($row['room_id'])?->firstWhere('number', $row['bed_number'])?->id;
    $row['visit_id'] = $context['visits']->get($row['visit_number'])?->id;

    return $row;
  }

  private static function hasMissingRequiredIds(array $row): bool
  {
    return empty($row['visit_id']) || empty($row['room_id']) || empty($row['bed_id']);
  }

  private static function handleVacatedBeds($noLongerOccupied, Carbon $now): void
  {
    if ($noLongerOccupied->isEmpty()) {
      return;
    }

    $bedIds = $noLongerOccupied->pluck('bed_id')->unique();

    DB::connection('patientlist')->table('bed_visits')
      ->whereIn('bed_id', $bedIds)
      ->update([
        'vacated_at' => $now,
        'updated_at' => $now,
      ]);

    DB::connection('patientlist')->table('beds')
      ->whereIn('id', $bedIds)
      ->update([
        'occupied_at' => null,
        'cleaned_at' => null,
        'updated_at' => $now,
      ]);

    foreach ($noLongerOccupied as $bedVisit) {
      $spaceId = self::resolveSpaceIdFromRoom($bedVisit->bed->room, $bedVisit->visit->number);

      $allowedDepartments = ['2214', '3112', '2112', '3111'];
      $skipThisBed = in_array($bedVisit->bed->room->number, ['100', '500'], true);

      if (
        in_array($bedVisit->bed->room->department->number, $allowedDepartments, true)
        && !$skipThisBed
      ) {
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
          $data['teamsMatchingAssignment'] = [3];
        }

        if ($bedVisit->bed->room->campus_id === 2) {
          $data['teamsMatchingAssignment'] = [6];
        }

        $taskService = new TaskService();
        $task = $taskService->create($data);

        broadcast(new BroadcastEvent($task, 'task_created', 'patient-service'));
      }
    }
  }

  private static function handleVisitsWithoutActiveBeds(): void
  {
    $visits = Visit::query()
      ->whereNull('discharged_at')
      ->where('admitted_at', '<=', Carbon::now()->subDay(2))
      ->whereDoesntHave('bedVisits', function ($q) {
        $q->whereNull('vacated_at');
      })
      ->pluck('number');

    if ($visits->isEmpty()) {
      return;
    }

    $results = collect();

    $visits->chunk(500)->each(function ($chunk) use (&$results) {
      $partial = DB::connection('oazis')
        ->table('OAZP.dbo.adt_visit')
        ->selectRaw("
          LTRIM(RTRIM(visit_id)) AS visit_id,
          CAST(adm_date AS DATETIME) + CAST(adm_time AS DATETIME) AS admitted_at,
          CAST(dis_date AS DATETIME) + CAST(dis_time AS DATETIME) AS discharged_at
        ")
        ->whereIn('visit_id', $chunk)
        ->get();

      $duplicateVisits = $partial
        ->groupBy('visit_id')
        ->filter(fn($rows) => $rows->count() > 1)
        ->keys();

      if ($duplicateVisits->isNotEmpty()) {
        throw new \Exception("Duplicate visit records detected for: " . $duplicateVisits->join(', '));
      }

      $results = $results->merge($partial);
    });

    foreach ($results as $row) {
      if (!$row->discharged_at) {
        logger("Visit {$row->visit_id} has no discharge date and no active bed");
      }

      Visit::where('number', $row->visit_id)
        ->update([
          'campus_id' => null,
          'department_id' => null,
          'bed_id' => null,
          'discharged_at' => $row->discharged_at,
        ]);
    }
  }

  private static function resolveSpaceIdFromRoom(Room $room, ?string $visitNumber = null): ?int
  {
    $spaceId = Space::where('SpcRoomNr', $room->number)
      ->where('campus_id', $room->campus_id)
      ->value('id');

    if (!$spaceId) {
      $spaceId = Space::where('name', 'like', '%' . $room->number . '%')
        ->where('campus_id', $room->campus_id)
        ->value('id');
    }

    if (!$spaceId) {
      logger("Room: {$room->number}" . ($visitNumber ? " for visit number: {$visitNumber}" : "") . " not found in Ultimo");
    }

    return $spaceId;
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
    if ($gender == '1' || strtoupper($gender) == 'M') {
      return 'M';
    }

    if ($gender == '2' || strtoupper($gender) == 'V') {
      return 'V';
    }

    return '';
  }

  public static function formatOazisCampus(string|null $campus)
  {
    if ($campus == '002') {
      return 'Deurne';
    }

    if ($campus == '001') {
      return 'Antwerpen';
    }

    return '';
  }
}
