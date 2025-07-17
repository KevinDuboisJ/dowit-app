<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PatientService;
use Illuminate\Support\Facades\Log;
use App\Events\BroadcastEvent;
use App\Models\Chain;

class HandlePatientVisits extends Command
{
    protected $signature = 'tasks:handle-patient-visits';
    protected $description = 'Handle scheduled patient visits';

    public function handle(PatientService $patientService)
    {
        $chain = Chain::firstWhere([
            'identifier' => 'patient-opname',
            'is_active' => true,
        ]);

        if ($chain) {
            $patientService->getOccupiedRooms($chain);
        }
    }
}
