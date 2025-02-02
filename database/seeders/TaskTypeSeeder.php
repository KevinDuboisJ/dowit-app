<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Remove all data and reset auto-increment
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('task_types')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('task_types')->insert([
            'id' => '1',
            'name' => 'Patiëntentransport',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        
        DB::table('task_types')->insert([
            'id' => '2',
            'name' => 'Poets',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
