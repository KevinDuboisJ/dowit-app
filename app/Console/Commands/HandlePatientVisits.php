<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PatientService;
use Illuminate\Support\Facades\Log;
use App\Events\BroadcastEvent;

class HandlePatientVisits extends Command
{
    protected $signature = 'tasks:handle-patient-visits';
    protected $description = 'Handle scheduled patient visits';

    public function handle(PatientService $patientService)
    {
        try {
            
            $patientService->getOccupiedRooms();

        } catch (\Exception $e) {
            Log::debug('HandlePatientVisits exception: ' . $e->getMessage());
            Log::debug('Exception trace: ' . $e->getTraceAsString());
        }
    }
}
