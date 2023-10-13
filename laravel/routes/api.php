<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SanctumAuthenticatedSessionController;
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
Route::prefix('users/me')->middleware('auth')->group(function () {
    Route::get('', function (Request $request) {
        dd($request->user());
        return $request->user();
    });
    Route::post('', [ResetInfosUsers::class,'username']);
    Route::post('reset-email', [ResetInfosUsers::class,'email']);
    Route::post('reset-password', [ResetInfosUsers::class,'password']);
});
