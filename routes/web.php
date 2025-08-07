<?php

use App\Http\Controllers\Pages\LoginController;
use App\Http\Controllers\Pages\DashboardController;
use App\Http\Controllers\Pages\AssetController;
use App\Http\Controllers\Pages\BedController;
use App\Http\Controllers\Pages\NewsfeedController;
use App\Http\Controllers\Pages\HelpController;
use App\Http\Controllers\Pages\TaskController;
use App\Http\Controllers\Pages\PatientController;
use App\Http\Controllers\Pages\UserController;
use App\Http\Controllers\Pages\TeamController;
use App\Http\Controllers\Pages\SpaceController;
use App\Http\Controllers\Pages\TagController;
use App\Http\Controllers\Pages\VisitController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['guest', HandleInertiaRequests::class]], function () {
  Route::get('login', [LoginController::class, 'login'])->name('login');
  Route::post('login', [LoginController::class, 'authenticate']);
});

Route::group(['middleware' => ['auth', HandleInertiaRequests::class]], function () {
  Route::get('/tasks/{id}', [TaskController::class, 'find'])->name('task.find');
  Route::get('/tasks/{id}/comments', [TaskController::class, 'comments'])->name('task.comments');
  Route::match(['get', 'post'], '/', [DashboardController::class, 'index'])->name('dashboard.index');
  Route::post('users/search', [UserController::class, 'search'])->name('user.search');
  Route::post('teams/search', [TeamController::class, 'search'])->name('team.search');
  Route::post('spaces/search', [SpaceController::class, 'search'])->name('space.search');
  Route::post('tags/search', [TagController::class, 'search'])->name('tag.search');
  Route::post('/announce', [DashboardController::class, 'announce'])->name('dashboard.announce');
  Route::post('/announce/{comment}/mark-as-read', [DashboardController::class, 'markAsRead'])->name('dashboard.markAsRead');
  Route::post('/task/store', [TaskController::class, 'store'])->name('task.store');
  Route::match(['post', 'delete'], '/task/{task}/update', [TaskController::class, 'update'])->name('task.update');
  Route::post('/visit/search', [VisitController::class, 'search'])->name('visit.search');
  Route::match(['get', 'post'], '/newsfeed', [NewsfeedController::class, 'index'])->name('newsfeed.index');
  Route::get('/beds', [BedController::class, 'index'])->middleware('auth');
  Route::get('/assets', [AssetController::class, 'index'])->name('asset.index');
  Route::any('help', [HelpController::class, 'index']);
  Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
