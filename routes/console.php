<?php


use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\HandleTaskPlanners;
use App\Console\Commands\FailoverHolidaySeed;
use App\Console\Commands\HandleLockers;
use App\Console\Commands\SeedHolidayDatabase;
use App\Console\Commands\HandleScheduledTasks;
use App\Services\LockerService;
use App\Services\PatientService;
use App\Services\TaskService;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Console Schedule
|--------------------------------------------------------------------------
|
| Here you may define your schedule. This will be executed once the script 
| is called. You may define as many schedule as you want.
| 
| 
|
*/

Schedule::command(HandleTaskPlanners::class)->everyMinute();
Schedule::command(HandleScheduledTasks::class)->everyMinute();
Schedule::command(SeedHolidayDatabase::class)->yearly(); // Seed holidays
Schedule::command(FailoverHolidaySeed::class)->yearlyOn(1, 2, '00:00'); // Runs January 2nd

Schedule::call(function (PatientService $patientService) { 
    $patientService->getOccupiedRooms(); 
})->everyFiveMinutes();

// Schedule::call(function (LockerService $lockerService, TaskService $taskService) { 
//     $lockerService->handleUnlockTasks($taskService); 
// })->everyMinute();