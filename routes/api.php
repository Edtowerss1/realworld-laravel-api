<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');
});

// RealWorld spec routes (no version prefix: /api/users/login, /api/user)
Route::post('/users/login', [AuthController::class, 'login'])->name('api.auth.login');
Route::post('/users', [AuthController::class, 'register'])->name('api.auth.register');

Route::middleware('auth:api')->group(function (): void {
    Route::get('/user', [AuthController::class, 'current'])->name('api.user.current');
    Route::put('/user', [AuthController::class, 'update'])->name('api.user.update');

});
