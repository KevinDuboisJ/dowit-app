<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TooltipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tooltips')->insert([
            'name' => 'loginUser',
            'text' => 'Vul uw active directory login in.',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('tooltips')->insert([
            'name' => 'managementModalSearch',
            'text' => 'Vul een zoekterm in en druk op enter.',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
