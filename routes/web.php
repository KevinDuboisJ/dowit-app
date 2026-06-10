<?php

use App\Http\Controllers\Pages\LoginController;
use App\Http\Controllers\Pages\DashboardController;
use App\Http\Controllers\Pages\AssetController;
use App\Http\Controllers\Pages\BedController;
use App\Http\Controllers\Pages\NewsfeedController;
use App\Http\Controllers\Pages\HelpController;
use App\Http\Controllers\Pages\TaskController;
use App\Http\Controllers\Pages\AnnouncementController;
use App\Http\Controllers\Pages\UserController;
use App\Http\Controllers\Pages\TeamController;
use App\Http\Controllers\Pages\SpaceController;
use App\Http\Controllers\Pages\TagController;
use App\Http\Controllers\Pages\VisitController;
use App\Http\Controllers\Pages\DeviceSelectionController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Login
Route::group(['middleware' => ['guest', HandleInertiaRequests::class]], function () {
    Route::match(['get', 'post'], 'login', [LoginController::class, 'login'])->name('login');
    Route::post('authenticate', [LoginController::class, 'authenticate']);
});

Route::group(['middleware' => ['auth', HandleInertiaRequests::class]], function () {
    Route::get('/select-device', [DeviceSelectionController::class, 'index'])->name('device-selection.index');
    Route::post('/select-device', [DeviceSelectionController::class, 'store'])->name('device-selection.store');

    Route::group(['middleware' => ['device.selected']], function () {
        // Dashboard
        Route::match(['get', 'post'], '/', [DashboardController::class, 'index'])
            ->name('dashboard.index');

        Route::match(['get', 'post'], '/requested-tasks', [DashboardController::class, 'requestedTasks'])
            ->name('dashboard.requested-tasks');

        // Tasks
        Route::get('/tasks/{id}', [TaskController::class, 'find'])->name('task.find');
        Route::get('/tasks/{id}/comments', [TaskController::class, 'comments'])->name('task.comments');
        Route::post('/task/store', [TaskController::class, 'store'])->name('task.store');
        Route::patch('/task/{task}/request-help', [TaskController::class, 'requestHelp'])->name('task.requestHelp');
        Route::match(['post', 'delete'], '/task/{task}/update', [TaskController::class, 'update'])->name('task.update');

        Route::post('users/search', [UserController::class, 'search'])->name('user.search');
        Route::post('teams/search', [TeamController::class, 'search'])->name('team.search');
        Route::post('spaces/search', [SpaceController::class, 'search'])->name('space.search');
        Route::post('tags/search', [TagController::class, 'search'])->name('tag.search');
        Route::post('/visit/search', [VisitController::class, 'search'])->name('visit.search');
        Route::match(['get', 'post'], '/newsfeed', [NewsfeedController::class, 'index'])->name('newsfeed.index');
        Route::get('/beds', [BedController::class, 'index'])->middleware('auth');
        Route::get('/assets', [AssetController::class, 'index'])->name('asset.index');
        Route::any('help', [HelpController::class, 'index']);

        // Announcements
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.announce');
        Route::post('/announcements/{announcement}/mark-as-read', [AnnouncementController::class, 'markAsRead'])->name('announcements.markAsRead');

        // Hearbeat
        Route::post('/me/heartbeat', function () {
            $user = auth()->user();

            if (! $user) {
                return response()->noContent();
            }

            $cacheKey = "users:{$user->id}:last_seen_write_lock";

            if (! Cache::has($cacheKey)) {
                $user->forceFill([
                    'last_seen_at' => now(),
                ])->save();

                Cache::put($cacheKey, true, now()->addMinutes(2));
            }

            return response()->noContent();
        })->middleware('auth')->name('presence.heartbeat');
    });

    // Logout
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
