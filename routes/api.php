<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ArticleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');
});

// public routes
Route::post('/users/login', [AuthController::class, 'login'])->name('api.auth.login');
Route::post('/users', [AuthController::class, 'register'])->name('api.auth.register');
Route::get('/profiles/{username}', [ProfileController::class, 'show'])->name('api.profiles.show');
Route::get('/articles', [ArticleController::class, 'index'])->name('api.articles.index');


// protected routes
Route::middleware('auth:api')->group(function (): void {
    Route::get('/user', [AuthController::class, 'current'])->name('api.user.current');
    Route::put('/user', [AuthController::class, 'update'])->name('api.user.update');

    Route::post('/profiles/{username}/follow', [ProfileController::class, 'follow'])->name('api.profiles.follow');
    Route::delete('/profiles/{username}/follow', [ProfileController::class, 'unfollow'])->name('api.profiles.unfollow');

    Route::get('/articles/feed', [ArticleController::class, 'feed'])->name('api.articles.feed');
    Route::post('/articles', [ArticleController::class, 'store'])->name('api.articles.store');
    Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('api.articles.update');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('api.articles.destroy');
    Route::post('/articles/{article}/favorite', [ArticleController::class, 'favorite'])->name('api.articles.favorite');
    Route::delete('/articles/{article}/favorite', [ArticleController::class, 'unfavorite'])->name('api.articles.unfavorite');

});

// public routes (to avoid route conflicts with protected routes)
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('api.articles.show');
