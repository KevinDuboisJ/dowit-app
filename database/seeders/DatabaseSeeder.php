<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('campuses')->truncate();
        DB::table('permission_role')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        // \App\Models\User::factory(10)->create();
        $this->call([
            CampusSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            PermissionRoleSeeder::class,
            TooltipSeeder::class,
            UserSeeder::class,
            TaskStatusSeeder::class,
            TaskTypeSeeder::class,
            HolidaySeeder::class,
            SettingSeeder::class,
            TeamSeeder::class,
        ]);
    }
}
