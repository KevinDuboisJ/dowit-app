<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            'name' => 'Super Admin',
            'code' => 'SUPER_ADMIN',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('roles')->insert([
            'name' => 'Admin',
            'code' => 'ADMIN',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        DB::table('roles')->insert([
            'name' => 'Gebruiker',
            'code' => 'USER',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
