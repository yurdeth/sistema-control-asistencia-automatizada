<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\NoBrowserCacheMiddleware;
use Illuminate\Support\Facades\Route;

// routes/api.php

Route::post('/login', [AuthController::class, "login"])->name('login');

Route::middleware(['auth:api', NoBrowserCacheMiddleware::class])->group(function () {
    Route::post('/logout', [AuthController::class, "logout"])->name('logout');
});
