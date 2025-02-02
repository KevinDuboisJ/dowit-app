<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permissions')->insert([
            'name' => 'Beheer gebruikers',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
            'name' => 'Zie items',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
            'name' => 'Zie patienten',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
            'name' => 'Zie kleuren',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('permissions')->insert([
            'name' => 'Zie rollen',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
