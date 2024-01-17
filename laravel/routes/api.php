<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SanctumAuthenticatedSessionController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ResetInfosUsers;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware('guest:sanctum')->post('/login', [SanctumAuthenticatedSessionController::class, 'store']);
Route::middleware('guest:sanctum')->post('/register', [RegisteredUserController::class, 'store']);
Route::prefix('users/me')->middleware(['auth:sanctum'])->group(function () {
    Route::get('', function (Request $request) {
        return $request->user();
    });
    Route::post('', [ResetInfosUsers::class,'username']);
    Route::post('reset-email', [ResetInfosUsers::class,'email']);
    Route::post('reset-password', [ResetInfosUsers::class,'password']);
});

Route::middleware(['auth:sanctum'])->get('game_user/{uuid}', [GameController::class, 'getGameUser']);
Route::prefix('game')->middleware(['auth:sanctum'])->group(function () {
    Route::post('check', [GameController::class, 'check']);
    Route::post('join', [GameController::class, 'join']);
    Route::post('start', [GameController::class, 'start']);
    Route::post('leave', [GameController::class, 'leave']);
    Route::post('move', [GameController::class, 'move']);
    Route::post('create', [GameController::class, 'create']);
    Route::get('/join-code/{code}', [GameController::class, 'get']);
    Route::put('/join-code/{code}', [GameController::class, 'update']);
    Route::get('/{uuid}', [GameController::class, 'get']);
});
