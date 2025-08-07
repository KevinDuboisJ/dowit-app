<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PATIENTLIST\Visit;

class VisitController extends Controller
{
  public function search(Request $request)
  {
    if (strlen($request->input('search')) === 8) {
      return response()->json(
        Visit::with(['patient', 'bed.room'])
          ->byVisitNumber($request->input('search'))
          ->byIsAdmitted()
          ->get()
      );
    }

    if (!is_numeric($request->input('search'))) {
      return response()->json(
        Visit::with('patient', 'bed.room')
          ->byPatientName($request->input('search'))
          ->byIsAdmitted()
          ->limit(40)
          ->get()
      );
    }
  }
}
