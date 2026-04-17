<?php

use App\Http\Controllers\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Client\RouteController;
use App\Http\Middleware\EnsureUserIsClient;
use Illuminate\Support\Facades\Route;

Route::post('register', [ClientAuthController::class, 'register']);
Route::post('login', [ClientAuthController::class, 'login']);
Route::get('routes', [RouteController::class, 'index']);
// Route::get('routes/{route}', [RouteController::class, 'show'])->whereNumber('route');

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsClient::class])->group(function (): void {
    Route::get('profile', [ClientAuthController::class, 'profile']);
    Route::put('profile', [ClientAuthController::class, 'updateProfile']);
    Route::post('logout', [ClientAuthController::class, 'logout']);

});
