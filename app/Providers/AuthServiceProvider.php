<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function getpermissions($roles) : Array {
        $permissions = Role::with('permissions')->whereIn('id', array_keys($roles))->get()->pluck('permissions')->flatten();
        $permissions = $permissions->map(function ($permission) {
            return $permission->name;
        })->toArray();
        return $permissions;
    }

    public function boot()
    {
        // Gate::define('seeAdminMenu', function (User $user, array $roles){
        //     return auth()->check() && in_array('Super Admin', $roles);
        // });

        // // Gate::define('seePatients', function (User $user, array $roles){
        // //     $permissions = $this->getpermissions($roles);
        // //     return auth()->check() && (in_array('Super Admin', $roles) || in_array('Zie patienten', $permissions));
        // // });

        // // Gate::define('seeColors', function (User $user, array $roles){
        // //     $permissions = $this->getpermissions($roles);
        // //     return auth()->check() && (in_array('Super Admin', $roles) || in_array('Zie kleuren', $permissions));
        // // });
        // Gate::define('seeItems', function (User $user, array $roles){
        //     $permissions = $this->getpermissions($roles);
        //     return auth()->check() && (in_array('Super Admin', $roles) || in_array('Zie items', $permissions));
        // });

        //
    }
}
