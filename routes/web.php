<?php

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\NoBrowserCacheMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Ruta principal - siempre accesible para todos
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Ruta de login - redirige a dashboard si ya está autenticado
Route::get('/login', function () {
    return Inertia::render('Auth/Login', [
        'canResetPassword' => true,
    ]);
})->middleware('guest.passport')->name('login');

// Grupo de rutas protegidas - requieren autenticación con Passport
Route::middleware(['web', NoBrowserCacheMiddleware::class, 'auth.passport'])->group(function () {
    // Dashboard - accesible para todos los usuarios autenticados
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'mustCheckAuth' => true
        ]);
    })->name('dashboard');

    // Rutas de administración

    Route::get('/catalogo', function () {
        return Inertia::render('Administration/classroomManagement/catalogo', [
            'mustCheckAuth' => true
        ]);
    })->middleware('role:1,6'); // Roles específicos o permitidos por rutas

    Route::get('/docentes', function () {
        return Inertia::render('Administration/General/docentes', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/estudiantes', function () {
        return Inertia::render('Administration/General/students', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/disponibilidad', function () {
        return Inertia::render('Administration/classroomManagement/availability', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/departamentos', function () {
        return Inertia::render('Administration/General/departments', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/informes', function () {
        return Inertia::render('Administration/General/reports', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/horarios', function () {
        return Inertia::render('Administration/classroomManagement/horarios', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/grupos', function () {
        return Inertia::render('Administration/classroomManagement/grupos', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/solicitudes-inscripcion', function () {
        return Inertia::render('Administration/General/solicitudesInscripcion', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/roles', function () {
        return Inertia::render('Administration/General/roles', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/materias', function () {
        return Inertia::render('Administration/General/materias', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/sesiones-clase', function () {
        return Inertia::render('Administration/classroomManagement/sesionesClase', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('tipos-recursos', function () {
        return Inertia::render('Administration/classroomManagement/tiposRecursos', [
            'mustCheckAuth' => true
        ]);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// en routes/web.php


require __DIR__ . '/auth.php';
