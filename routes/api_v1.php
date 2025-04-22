<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TooltipController;
use App\Http\Controllers\Api\V1\TaskChainController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/tooltip', [TooltipController::class, 'find'])->name('tooltip.find');
Route::put('/users/edbid/{user:edb_id}', [UserController::class, 'updateByEdbId'])->name('users.edbid.update');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::post('/task-chains/trigger', [TaskChainController::class, 'trigger']);