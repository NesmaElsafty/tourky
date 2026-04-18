<?php

use App\Http\Controllers\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Client\NotificationController as ClientNotificationController;
use App\Http\Controllers\Client\ReservationController as ClientReservationController;
use App\Http\Controllers\Client\RouteController;
use App\Http\Controllers\Client\TermController as ClientTermController;
use App\Http\Middleware\EnsureUserIsClient;
use Illuminate\Support\Facades\Route;

Route::post('register', [ClientAuthController::class, 'register']);
Route::post('login', [ClientAuthController::class, 'login']);
Route::get('routes', [RouteController::class, 'index']);
Route::get('terms', [ClientTermController::class, 'index']);
Route::get('notifications', [ClientNotificationController::class, 'index']);
// Route::get('routes/{route}', [RouteController::class, 'show'])->whereNumber('route');

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsClient::class])->group(function (): void {
    Route::get('profile', [ClientAuthController::class, 'profile']);
    Route::put('profile', [ClientAuthController::class, 'updateProfile']);
    Route::post('logout', [ClientAuthController::class, 'logout']);

    Route::get('reservations', [ClientReservationController::class, 'index']);
    Route::post('reservations', [ClientReservationController::class, 'store']);
    Route::patch('reservations/{reservation}/cancel', [ClientReservationController::class, 'cancel']);
    Route::delete('reservations/{reservation}', [ClientReservationController::class, 'destroy']);
});
