<?php

use App\Http\Controllers\Captain\AuthController as CaptainAuthController;
use App\Http\Middleware\EnsureUserIsCaptain;
use Illuminate\Support\Facades\Route;

Route::post('register', [CaptainAuthController::class, 'register']);
Route::post('login', [CaptainAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsCaptain::class])->group(function (): void {
    Route::get('profile', [CaptainAuthController::class, 'profile']);
    Route::put('profile', [CaptainAuthController::class, 'updateProfile']);
    Route::post('logout', [CaptainAuthController::class, 'logout']);
});
