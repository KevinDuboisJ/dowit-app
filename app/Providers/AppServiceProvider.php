<?php

namespace App\Providers;

use App\Contracts\UserAuthenticator;
use App\Jobs\CreateTasksJob;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Services\LoginService;
use App\Services\PincodeLoginService;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use App\Models\Comment;
use App\Observers\UserObserver;
use App\Observers\TeamObserver;
use App\Observers\BroadcastObserver;
use Filament\Facades\Filament;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserAuthenticator::class, function ($app) {
            return $app->make(strlen(request()->password) > 10 ? LoginService::class : PincodeLoginService::class);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
       
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                'Taakconfigurator',
                'Instellingen',
            ]);
        });
    
        Model::unguard();
        Model::preventLazyLoading(! app()->isProduction());
        User::observe(UserObserver::class);
        Team::observe(TeamObserver::class);
        Task::observe(BroadcastObserver::class);
        Comment::observe(BroadcastObserver::class);
    }
}
