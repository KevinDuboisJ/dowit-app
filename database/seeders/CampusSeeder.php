<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CampusSeeder extends Seeder
{
    protected $connection;

    public function __construct($connection = 'mysql')
    {
        $this->connection = $connection;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection($this->connection)->table('campuses')->insert([
            [
                'name' => 'Campus Antwerpen',
                'acronym' => 'CA',
                'address' => 'Harmoniestraat 68, 2018 Antwerpen',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'name' => 'Campus Deurne',
                'address' => 'Florent Pauwelslei 1, 2100 Deurne',
                'acronym' => 'CD',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}
