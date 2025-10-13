<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\NoBrowserCacheMiddleware;
use App\Http\Middleware\RoleBasedMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/login', function () {
    return Inertia::render('Auth/Login', [
        'canResetPassword' => true,
    ]);
})->name('login');

/*Route::get('/dashboard', function () {
    // Renderiza el dashboard y pasa una bandera
    return Inertia::render('Dashboard', [
        'mustCheckAuth' => true
    ]);
})->name('dashboard');*/

Route::middleware(['auth:api', NoBrowserCacheMiddleware::class])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'mustCheckAuth' => true
        ]);
    })->name('dashboard');

    // Rutas de administración
    Route::get('/catalogo', function () {
        return Inertia::render('Administration/classroomManagement/catalogo');
    });

    Route::get('/disponibilidad', function () {
        return Inertia::render('Administration/classroomManagement/availability');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// en routes/web.php



require __DIR__.'/auth.php';
