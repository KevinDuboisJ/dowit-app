<?php

namespace App\Services;

use App\Models\Chain;
use App\Models\PATIENTLIST\Patient;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Contracts\ChainAction;
use App\Models\Task;
use App\Exceptions\InvalidHl7MessageException;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskPriorityEnum;
use App\Events\BroadcastEvent;
use App\Services\PatientService;

class Hl7AdmissionAction implements ChainAction
{
  public function handle($context, Chain $chain)
  {
    $hl7 = $context instanceof Request
      ? $context->getContent()
      : (string) $context;

    if (blank($hl7)) {
      throw new InvalidHl7MessageException();
    }

    $segments = preg_split('/\r?\n/', trim($hl7));
    $messageType = $this->getMessageType($segments);

    return match ($messageType) {
      //'A01' => $this->parseA01($segments, $chain),
      'A02' => $this->parseA02($segments, $chain),
      'A03' => $this->parseA03($segments, $chain),
      default => throw new InvalidHl7MessageException("Unsupported message type: {$messageType}"),
    };
  }

  private function getMessageType(array $segments): ?string
  {
    foreach ($segments as $seg) {
      if (str_starts_with($seg, 'MSH|')) {
        $parts = explode('|', $seg);
        if (isset($parts[8])) {
          [, $trigger] = explode('^', $parts[8] . '^');
          return $trigger;
        }
      }
    }
    return null;
  }

  // private function parseA01(array $segments, Chain $chain): void
  // {
  //   $data = [];

  //   foreach ($segments as $seg) {
  //     $parts = explode('|', $seg);
  //     $id = array_shift($parts);
  //     $f = $parts;

  //     match ($id) {
  //       'PID' => $this->parsePID($f, $data),
  //       'PV1' => $this->parsePV1($f, $data),
  //       default => null
  //     };
  //   }

  // }

  private function parseA02(array $segments, Chain $chain): void
  {
    $data = [];

    foreach ($segments as $seg) {
      $parts = explode('|', $seg);
      $id = array_shift($parts);
      $f = $parts;

      match ($id) {
        'PID' => $this->parsePID($f, $data),
        'PV1' => $this->parsePV1($f, $data),
        default => null
      };
    }

    $visit = PatientService::createOrUpdateVisit($data);

    $task = Task::create(
      [
        'name' => 'Eindpoets patiëntkamer',
        'start_date_time' => carbon::now(),
        'description' => "Kamer {$data['room_number']}, Bed {$data['bed_number']} - " . strtoupper($data['lastname']),
        'campus_id' => $data['campus_id'],
        'task_type_id' => '3',
        'space_id' => $visit->space_id,
        'status_id' => TaskStatusEnum::Added->value,
        'priority' => TaskPriorityEnum::Medium->value,
        'visit_id' => $visit->id,
      ],
    );

    TaskAssignmentService::assignTaskToTeams($task, $chain->teams->pluck('id')->toArray());

    broadcast(new BroadcastEvent($task, 'task_created', get_class($this)));
  }

  private function parseA03(array $segments, Chain $chain): void
  {
    $data = [];

    foreach ($segments as $seg) {
      $parts = explode('|', $seg);
      $id = array_shift($parts);
      $f = $parts;

      match ($id) {
        'PID' => $this->parsePID($f, $data),
        'PV1' => $this->parsePV1($f, $data),
        default => null
      };
    }

    // This code has to be adjusted for createOrUpdateVisitByContext
    // $visit = PatientService::createOrUpdateVisit($data);

    $task = Task::create(
      [
        'name' => 'Eindpoets patiëntkamer',
        'start_date_time' => carbon::now(),
        'description' => "Kamer {$data['room_number']}, Bed {$data['bed_number']} - " . strtoupper($data['lastname']),
        'campus_id' => $data['campus_id'],
        'task_type_id' => '3',
        'space_id' => $visit->space_id,
        'status_id' => TaskStatusEnum::Added->value,
        'priority' => TaskPriorityEnum::Medium->value,
        'visit_id' => $visit->id,
      ],
    );

    TaskAssignmentService::assignTaskToTeams($task, $chain->teams->pluck('id')->toArray());

    broadcast(new BroadcastEvent($task, 'task_created', get_class($this)));
  }

  private function parsePID(array $f, array &$data): void
  {
    $data['patient_number'] = $f[2] ?? null;

    if (!empty($f[4])) {
      [$lastname, $firstname] = explode('^', $f[4] . '^');
      $data['lastname'] = $lastname;
      $data['firstname'] = $firstname;
    }

    $data['birthdate'] = !empty($f[6])
      ? Carbon::createFromFormat('YmdHis', $f[6])->toDateString()
      : null;

    $data['gender'] = $f[7] ?? null;

    if (!empty($f[10])) {
      $parts = explode('^', $f[10]);
      $data['address']     = $parts[0] ?? null;
      $data['city']        = $parts[2] ?? null;
      $data['postal_code'] = $parts[4] ?? null;
      $data['country']     = $parts[5] ?? null;
    }

    $data['phone'] = isset($f[12]) ? explode('^', $f[12])[0] : null;
  }

  private function parsePV1(array $f, array &$data): void
  {
    $this->parsePV1Admission($f, $data);
    $this->parsePV1Discharge($f, $data);
    $this->parsePV1Location($f, $data);
  }

  private function parsePV1Admission(array $f, array &$data): void
  {
    $data['visit_number'] = $f[18] ?? null;
    if (!empty($f[43])) {
      $data['admitted_at'] = Carbon::createFromFormat('YmdHis', $f[43])->toDateTimeString();
    }
  }

  private function parsePV1Discharge(array $f, array &$data): void
  {
    if (!empty($f[44])) {
      $data['discharged_at'] = Carbon::createFromFormat('YmdHis', $f[44])->toDateTimeString();
    }
  }

  private function parsePV1Location(array $f, array &$data): void
  {
    if (!empty($f[2])) {
      [$dept, $room, $bed, $campus] = array_pad(explode('^', $f[2]), 3, null);
      $data['department_number'] = $dept;
      $data['room_number'] = $room;
      $data['bed_number'] = $bed;
      $data['campus_id'] = ltrim($campus, '0');
    }
  }
}
