<?php

use App\Http\Controllers\TrackingSocketAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('tracking/socket')->middleware(['auth:sanctum'])->group(function (): void {
    Route::get('me', [TrackingSocketAuthController::class, 'me']);
    Route::post('trip-authorize', [TrackingSocketAuthController::class, 'authorizeTrip']);
});

Route::prefix('admin')->group(base_path('routes/api/admin.php'));
Route::prefix('captain')->group(base_path('routes/api/captain.php'));
Route::prefix('client')->group(base_path('routes/api/client.php'));
