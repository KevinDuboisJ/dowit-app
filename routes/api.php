<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TooltipController;
use App\Http\Controllers\Api\V1\UserController;


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
Route::put('/users/edbid/{user:edb_id}', [UserController::class, 'updateByEdbId'])->name('users.edbid.update');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/tooltip', [TooltipController::class, 'getTooltip']);