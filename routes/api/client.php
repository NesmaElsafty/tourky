<?php

use App\Http\Controllers\Client\AuthController as ClientAuthController;
use App\Http\Controllers\UserMediaController;
use App\Http\Middleware\EnsureUserIsClient;
use Illuminate\Support\Facades\Route;

Route::post('register', [ClientAuthController::class, 'register']);
Route::post('login', [ClientAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'locale.user', EnsureUserIsClient::class])->group(function (): void {
    Route::get('profile', [ClientAuthController::class, 'profile']);
    Route::put('profile', [ClientAuthController::class, 'updateProfile']);
    Route::post('logout', [ClientAuthController::class, 'logout']);

    Route::post('media/avatar', [UserMediaController::class, 'storeAvatar']);
    Route::delete('media/avatar', [UserMediaController::class, 'destroyAvatar']);
    Route::post('media/documents', [UserMediaController::class, 'storeDocument']);
    Route::delete('media/documents/{media}', [UserMediaController::class, 'destroyDocument']);
});
