<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\OAZIS\Patient;
use App\Models\PATIENTLIST\Patient as PatientListPatient;

class PatientController extends Controller
{
  public function getPatient(Request $request)
  {
    if (strlen($request->input('visitId')) === 8) {
      return response()->json(PatientListPatient::getByPatientVisitId($request->input('visitId')));
    }

    if (!is_numeric($request->input('visitId'))) {
      return response()->json(PatientListPatient::getByPatientName($request->input('visitId')));
    }
  }
}
