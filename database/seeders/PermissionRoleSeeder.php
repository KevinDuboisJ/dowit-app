<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all the roles
        $permissions = Permission::all()->pluck('id', 'name');
        Role::all()->each(function ($roles) use ($permissions) {
            
                if($roles->name =='Super Admin'){
                
                $roles->permissions()->attach(
                    $permissions->values()->toArray()
                );
            }
            if($roles->name =='Gebruiker') {
                $roles->permissions()->attach(
                    $permissions->get('view_item')
                );
            }
            if($roles->name =='Bezoeker') {
                $roles->permissions()->attach(
                    $permissions->get('see_colors')
                );
                $roles->permissions()->attach(
                    $permissions->get('see_patients')
                );
            }
        });
    }
}
