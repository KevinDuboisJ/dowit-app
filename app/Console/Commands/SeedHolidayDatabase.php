<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedHolidayDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with holiday data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Artisan::call('db:seed', ['--class' => 'HolidaySeeder']);
        return 0;
    }
}
