<?php

namespace App\Services;

use App\Models\Chain;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Contracts\ChainAction;
use App\Models\PATIENTLIST\Patient;

class Hl7AdmissionAction implements ChainAction
{
  public function handle($context, Chain $chain): void
  {
    // $context is the full Request → raw HL7 in body
    $hl7 = $context instanceof Request
      ? $context->getContent()
      : (string) $context;

    // 1) parse segments (your own library or custom code)
    $segments = preg_split('/\r?\n/', trim($hl7));
    $data = [];
    foreach ($segments as $seg) {
      [$id, ...$f] = explode('|', $seg);
      if ($id === 'PID') {
        $data['patient_id'] = $f[2] ?? null;
        $data['lastname']   = $f[0] ?? null;
        $data['firstname']  = $f[1] ?? null;
        $data['gender']     = $f[7] ?? null;
        $data['birthdate']  = isset($f[6])
          ? Carbon::parse($f[6])->toDateString()
          : null;
      }
      if ($id === 'PV1') {
        $data['visit_id']   = $f[19] ?? null;
        $data['admission']  = isset($f[44])
          ? Carbon::parse($f[44])
          : null;
        $data['discharge']  = isset($f[45])
          ? Carbon::parse($f[45])
          : null;
      }
    }

    // 2) upsert patient
    $patient = Patient::updateOrCreate(
      ['patient_id' => $data['patient_id'], 'visit_id' => $data['visit_id']],
      $data
    );

    // 3) optionally chain a follow-up task, or dispatch events, etc.
    //    e.g. if $chain->trigger_conditions say 'gender' => 'F', you can
    //    re-check them here, then:
    if (($chain->trigger_conditions['gender'] ?? null) === $data['gender']) {
      \App\Models\Task::create([
        'task_type'  => $chain->action_params['task_type'] ?? 'check-chart',
        'title'      => "Female admission follow-up",
        'related_id' => $patient->id,
      ]);
    }

    // …or fire off more chains with ChainExecutor::executeInternal(…) etc.
  }
}
