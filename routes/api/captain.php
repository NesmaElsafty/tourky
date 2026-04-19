<?php

use App\Http\Controllers\Captain\AuthController as CaptainAuthController;
use App\Http\Controllers\Captain\NotificationController as CaptainNotificationController;
use App\Http\Controllers\Captain\TermController as CaptainTermController;
use App\Http\Controllers\Captain\TripController as CaptainTripController;
use App\Http\Middleware\EnsureUserIsCaptain;
use Illuminate\Support\Facades\Route;

Route::post('register', [CaptainAuthController::class, 'register']);
Route::post('login', [CaptainAuthController::class, 'login']);

Route::get('terms', [CaptainTermController::class, 'index']);

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsCaptain::class])->group(function (): void {
    Route::get('profile', [CaptainAuthController::class, 'profile']);
    Route::put('profile', [CaptainAuthController::class, 'updateProfile']);
    Route::post('logout', [CaptainAuthController::class, 'logout']);

    Route::get('notifications', [CaptainNotificationController::class, 'index']);
    Route::patch('notifications/{deliveryId}/markAsRead', [CaptainNotificationController::class, 'markAsRead']);
    Route::patch('notifications/markAllAsRead', [CaptainNotificationController::class, 'markAllAsRead']);

    Route::get('trips', [CaptainTripController::class, 'index']);
    Route::post('trips/{trip}/start', [CaptainTripController::class, 'start'])->whereNumber('trip');
    Route::get('trips/{trip}', [CaptainTripController::class, 'show'])->whereNumber('trip');
    Route::patch('trips/{trip}/reservations/{reservation}/pickup', [CaptainTripController::class, 'confirmPickup'])
        ->whereNumber('trip')
        ->whereNumber('reservation');
    Route::patch('trips/{trip}/reservations/{reservation}/dropoff', [CaptainTripController::class, 'confirmDropoff'])
        ->whereNumber('trip')
        ->whereNumber('reservation');
    Route::post('trips/{trip}/close', [CaptainTripController::class, 'close'])->whereNumber('trip');
});
