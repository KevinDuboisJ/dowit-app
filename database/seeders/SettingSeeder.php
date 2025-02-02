<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            'id' => 1,  // Assuming `id` is auto-increment, remove this if needed
            'name' => 'Taakprioriteit',
            'code' => 'TASK_PRIORITY',
            'value' => json_encode([
                "Low" => ["time" => "60", "color" => "#16a34a"],
                "High" => ["time" => "600", "color" => "#dc2626"],
                "Medium" => ["time" => "180", "color" => "#fb923c"]
            ]),
            'type' => 'global',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
