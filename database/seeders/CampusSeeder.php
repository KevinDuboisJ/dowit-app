<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('campuses')->insert([
            'name' => 'Campus Antwerpen',
            'acronym' => 'CA',
            'address' => 'Harmoniestraat 68, 2018 Antwerpen',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('campuses')->insert([
            'name' => 'Campus Deurne',
            'address' => 'Florent Pauwelslei 1, 2100 Deurne',
            'acronym' => 'CD',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        // DB::table('campuses')->insert([
        //     'name' => 'Cinema',
        //     'address' => 'Herentalsebaan 369, 2100 Antwerpen',
        //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        //     'acronym' => 'CIN',
        // ]);
        // DB::table('campuses')->insert([
        //     'name' => 'Universitair Ziekenhuis Antwerpen',
        //     'acronym' => 'UZA',
        //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        // ]);
        // DB::table('campuses')->insert([
        //     'name' => 'Privépraktijk',
        //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        //     'acronym' => 'Ext',
        // ]);
        // DB::table('campuses')->insert([
        //     'name' => 'Blancefloer',
        //     'address' => 'Blancefloerlaan 153, 2050 Antwerpen',
        //     'acronym' => 'BL',
        //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        // ]);
        // DB::table('campuses')->insert([
        //     'name' => 'Orthoca Kielsevest',
        //     'address' => 'Kielsevest 14, 2018 Antwerpen',
        //     'acronym' => 'KV',
        //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        // ]);
        // DB::table('campuses')->insert([
        //     'name' => 'Orthoca Noord',
        //     'address' => 'Handelslei 28, 2960 Brecht (Sint-Job-in-’t-Goor)',
        //     'acronym' => 'ON',
        //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        // ]);
        // DB::table('campuses')->insert([
        //     'name' => 'Polikliniek Stevenslei',
        //     'acronym' => 'PS',
        //     'address' => 'Stevenslei 20, 2100 Deurne',
        //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        // ]);
    }
}