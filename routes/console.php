<?php


use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\HandleTaskPlanners;
use App\Console\Commands\FailoverHolidaySeed;
use App\Console\Commands\FailoverTaskPlanners;
use App\Console\Commands\SeedHolidayDatabase;
use App\Console\Commands\HandlePatientVisits;
use App\Console\Commands\HandleScheduledTasks;

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
Schedule::command(HandlePatientVisits::class)->everyTenMinutes();