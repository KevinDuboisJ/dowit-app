<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\OAZIS\Patient;

class PatientController extends Controller
{
  public function getPatient(Request $request)
  {
    return response()->json(Patient::getByPatientId($request->input('visitId')));
  }

}