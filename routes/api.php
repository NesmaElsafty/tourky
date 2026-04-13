<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(base_path('routes/api/admin.php'));
Route::prefix('captain')->group(base_path('routes/api/captain.php'));
Route::prefix('client')->group(base_path('routes/api/client.php'));
