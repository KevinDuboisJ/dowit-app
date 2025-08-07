<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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
use App\Models\PATIENTLIST\Campus;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientService
{

  public static function getOccupiedRooms(Chain $chain)
  {
    try {
      $now = Carbon::now();
      $context = self::preloadTables();
      $resultsMap = [];
      $inserts = [];

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
        -- combine date + time into one column
        CAST(av.adm_date AS DATETIME) + CAST(av.adm_time AS DATETIME) AS admitted_at,
        CAST(av.dis_date AS DATETIME) + CAST(av.dis_time AS DATETIME) AS discharged_at 
        FROM OAZP.dbo.BEDGRID AS bg
        LEFT JOIN adt_visit AS av ON bg.VISIT_ID = av.visit_id
        WHERE PATINDEX('%[^0-9]%', bg.ROOM_ID) = 0
        ORDER BY bg.ROOM_ID;";

      $results = DB::connection('oazis')->select($query);

      $activeVisits = BedVisit::on('patientlist')
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

      foreach ($results as $row) {
        $row = array_map([Helper::class, 'trimOrNull'], (array) $row);
        self::createOrUpdateVisitByContext($row, $context);

        $row['room_id'] = $context['rooms']->get(self::getRoomKey($row['campus_id'], $row['room_number']))?->id;
        $row['bed_id'] = $context['beds']->get($row['room_id'])?->firstWhere('number', $row['bed_number'])?->id;
        $row['visit_id'] = $context['visits']->get($row['visit_number'])?->id;

        if (empty($row['visit_id']) || empty($row['room_id']) || empty($row['bed_id'])) {
          logger("OAZIS data inconsistency: Room ID, Bed ID or Visit ID is missing for visit {$row['visit_id']}, room {$row['room_id']}, bed {$row['bed_id']}");
          continue; // Skip this row if any of the IDs are missing
        }

        $resultsMap[self::getBedVisitKey($row['visit_id'], $row['bed_id'])] = $row;
      }

      $noLongerOccupied = $activeVisits->filter(fn($_, $key) => !isset($resultsMap[$key]));
      $newlyOccupied = collect($resultsMap)->filter(fn($_, $key) => !isset($activeVisits[$key]));

      // Update desocupied beds
      if ($noLongerOccupied->isNotEmpty()) {

        $bedIds = $noLongerOccupied
          ->pluck('bed_id')
          ->unique();

        // Set bed_visits vacated at
        DB::connection('patientlist')->table('bed_visits')
          ->whereIn('bed_id', $bedIds)
          ->update(['vacated_at' => $now, 'updated_at' => $now]);

        // Set beds as not occupied
        DB::connection('patientlist')->table('beds')
          ->whereIn('id', $bedIds)
          ->update(['occupied_at' => null, 'cleaned_at' => null, 'updated_at' => $now]);

        foreach ($noLongerOccupied as $bedVisit) {
          // TODO: Update the discharge date in the visit here, as this is the point where we know the visit is no longer active.
          // Keep in mind that it could also be a transfer, so handle that case accordingly.

          $spaceId = Space::where('SpcRoomNr', $bedVisit->bed->room->number)->where('campus_id', $bedVisit->bed->room->campus_id)->value('id'); // Fast with index

          if (!$spaceId) {
            $spaceId = Space::where('name', 'like', '%' . $bedVisit->bed->room->number . '%')->where('campus_id', $bedVisit->bed->room->campus_id)->value('id'); // Slower fallback
          }

          if (!$spaceId) {
            logger("Kamer niet gevonden in Ultimo: Eindpoets patiëntkamer Kamer {$bedVisit->bed->room->number}, Bed {$bedVisit->bed->number} - " . strtoupper($bedVisit->visit->patient->lastname));
          }

          // $task = Task::create(
          //   [
          //     'name' => 'Eindpoets patiëntkamer',
          //     'start_date_time' => $now,
          //     'description' => "Kamer {$bedVisit->bed->room->number}, Bed {$bedVisit->bed->number} - " . strtoupper($bedVisit->visit->patient->lastname),
          //     'campus_id' => $bedVisit->bed->room->campus_id,
          //     'task_type_id' => TaskTypeEnum::EndOfStayCleaning->value,
          //     'space_id' => $spaceId ?? null,
          //     'priority' => TaskPriority::Medium->name,
          //     'bed_visit_id' => $bedVisit->id,
          //   ],
          // );

          // if ($bedVisit->bed->room->campus_id === 1) {
          //   $task->teams()->sync([5]); // Teams id for Antwerp
          // }

          // if ($bedVisit->bed->room->campus_id === 2) {
          //   $task->teams()->sync([6]); // Teams id for Deurne
          // }

          // broadcast(new BroadcastEvent($task, 'task_created', $chain->identifier));
        }
      }

      // Insert new visits
      foreach ($newlyOccupied as $key => $data) {

        if (empty($data['bed_id']) || empty($data['visit_id'])) {
          $dataString = json_encode($data);
          Log::warning("Missing bed_id or visit_id for new visit: {json_encode($dataString)}");
        }

        $inserts[] = [
          'bed_id'     => $data['bed_id'],
          'visit_id'   => $data['visit_id'],
          'occupied_at' => $now,
          'created_at' => $now,
          'updated_at' => $now,
        ];
      }

      if (!empty($inserts)) {
        DB::connection('patientlist')->table('bed_visits')->insert($inserts);
        $bedIds = array_unique(array_column($inserts, 'bed_id'));

        Bed::whereIn('id', $bedIds)
          ->update([
            'occupied_at' => $now,
          ]);
      }

      $visits = Visit::query()
        ->whereNull('discharged_at') // only active visits
        ->whereNotNull('bed_id')
        ->whereDoesntHave('bedVisits', function ($q) {
          $q->whereNull('vacated_at'); // bed is still occupied
        })
        ->pluck('number'); // get only the visit number

      if ($visits->isNotEmpty()) {

        $results = collect(); // will hold the final merged result

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

          // Detect duplicates
          $duplicateVisits = $partial
            ->groupBy('visit_id')
            ->filter(fn($rows) => $rows->count() > 1)
            ->keys();

          if ($duplicateVisits->isNotEmpty()) {
            $duplicateVisitNumber = $duplicateVisits->join(', ');
            throw new \Exception("In PatientService, while updating the discharged_at value, multiple Visit records were found for visit number: $duplicateVisitNumber. Cause unknown, further investigation required");
          }

          $results = $results->merge($partial);
        });

        foreach ($results as $row) {

          if (!$row->discharged_at) {
            logger("Visit ID $row->visit_id has no assigned bed and also doesnt have a discharge date ");
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
      Log::debug([
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
      ]);
    }
  }

  public static function getBedVisitKey($visitId, $bedId)
  {
    return "{$visitId}_{$bedId}";
  }

  public static function getRoomKey($campusId, $roomNumber)
  {
    return $campusId . '_' . $roomNumber;
  }

  public static function createOrUpdateVisitByContext($data, &$context)
  {
    $department = $context['departments']->get($data['department_number']);

    if (!$department) {
      $department = Department::create(['number' => $data['department_number']]);
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
      $room->update(['department_id' => $department->id]);
      $context['rooms'][$roomKey] = $room; // Update context with new assignment
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

    // Patient
    $patient = $context['patients']->get($data['patient_number']) ?? new Patient(['number' => $data['patient_number']]);
    $patient->fill([
      'firstname' => $data['firstname'],
      'lastname'  => $data['lastname'],
      'gender'    => self::formatOazisGender($data['gender']),
    ]);

    if ($patient->isDirty()) {
      $patient->save();
    }

    $context['patients'][$patient->number] = $patient;

    // Visit
    $visit = $context['visits']->get($data['visit_number']) ?? new Visit(['number' => $data['visit_number']]);

    $visit->fill([
      'patient_id'    => $patient->id,
      'campus_id'     => $data['campus_id'],
      'department_id' => $department->id,
      'bed_id'        => $bed->id,
      'admitted_at'   => $data['admitted_at'] ?? $visit->admitted_at ?? null,
      'discharged_at' => $data['discharged_at'] ?? $visit->discharged_at ?? null,
    ]);

    if ($visit->isDirty()) {
      $visit->save();
    }

    $context['visits'][$visit->number] = $visit;
  }

  private static function preloadTables()
  {
    return [
      'departments' => Department::all()->keyBy('number'),
      'rooms' => Room::all()->keyBy(fn($room) => self::getRoomKey($room->campus_id, $room->number)),
      'beds' => Bed::all()->groupBy('room_id')->map(function ($group) {
        return $group->keyBy('number');
      }),
      'patients' => Patient::all()->keyBy('number'),
      'visits' => Visit::all()->keyBy('number'),
    ];
  }

  public static function handleFinalCleanTask(Task $task): void
  {
    if ($task->status_id === TaskStatus::Completed->value && $task->bed_visit_id) {

      $taskBedVisit = $task->bedVisit;

      if ($taskBedVisit) {
        $now = now();

        $taskBedVisit->update(['cleaned_at' => $now]);

        $hasNewerVisit = BedVisit::where('bed_id', $taskBedVisit->bed_id)
          ->where('occupied_at', '>', $taskBedVisit->occupied_at)
          ->exists();

        if (!$hasNewerVisit) {
          $taskBedVisit->bed->update(['cleaned_at' => now()]);
        }
      } else {
        logger('handleFinalCleanTask: Geen huidige bedbezoek gevonden voor taak: ' . $task->id);
      }
    }
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
