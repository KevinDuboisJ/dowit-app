<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Log;

class JobQueuedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(JobQueued $event): void
    {
        // $existingJobs = JobTracking::where('task_configuration_id', $event->configuration_id)->first();
        // DB::table('jobs')->where()->get();
        
    }
}
