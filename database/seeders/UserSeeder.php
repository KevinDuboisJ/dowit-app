<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'firstname' => 'Systeem',
            'lastname' => '',
            'username' => '',
            'image_path' => 'dummy-profile.png',
            'email' => 'helpdesk@azmonica.be',
            'is_active' => true,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
