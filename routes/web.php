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

Route::get('/activation', function () {
    return view('auth.userActivation');
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

    // Rutas de perfil (mantener fuera de /dashboard)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== RUTAS REESTRUCTURADAS CON PREFIJO /DASHBOARD/ =====
// Grupo de rutas del dashboard con estructura jerárquica
Route::middleware(['web', NoBrowserCacheMiddleware::class, 'auth.passport'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {

        // Gestión de Aulas
        Route::get('/catalogo', function () {
            return Inertia::render('Administration/classroomManagement/catalogo', [
                'mustCheckAuth' => true
            ]);
        })->middleware('role:1,6')->name('catalogo'); // Roles específicos o permitidos por rutas

        Route::get('/disponibilidad', function () {
            return Inertia::render('Administration/classroomManagement/availability', [
                'mustCheckAuth' => true
            ]);
        })->name('disponibilidad');

        Route::get('/horarios', function () {
            return Inertia::render('Administration/classroomManagement/horarios', [
                'mustCheckAuth' => true
            ]);
        })->name('horarios');

        Route::get('/tipos-recursos', function () {
            return Inertia::render('Administration/classroomManagement/tiposRecursos', [
                'mustCheckAuth' => true
            ]);
        })->name('tipos-recursos');

        Route::get('/sesiones-clase', function () {
            return Inertia::render('Administration/classroomManagement/sesionesClase', [
                'mustCheckAuth' => true
            ]);
        })->name('sesiones-clase');

        Route::get('/grupos', function () {
            return Inertia::render('Administration/classroomManagement/grupos', [
                'mustCheckAuth' => true
            ]);
        })->name('grupos');

        // Consultar disponibilidad de aulas por día y hora
        Route::get('/consultar-disponibilidad', function () {
            return Inertia::render('Administration/classroomManagement/consultarDisponibilidad', [
                'mustCheckAuth' => true
            ]);
        })->name('consultar-disponibilidad'); // role:5 = DOCENTE
        
        // Historial personal de uso de aulas del docente
        Route::get('/mi-historial-aulas', function () {
            return Inertia::render('Administration/classroomManagement/historialAulas', [
                'mustCheckAuth' => true
            ]);
        })->name('mi-historial-aulas'); // role:5 = DOCENTE

        // Gestión General
        Route::get('/departamentos', function () {
            return Inertia::render('Administration/General/departments', [
                'mustCheckAuth' => true
            ]);
        })->name('departamentos');

        Route::get('/docentes', function () {
            return Inertia::render('Administration/General/docentes', [
                'mustCheckAuth' => true
            ]);
        })->name('docentes');

        Route::get('/estudiantes', function () {
            return Inertia::render('Administration/General/students', [
                'mustCheckAuth' => true
            ]);
        })->name('estudiantes');

        Route::get('/solicitudes-inscripcion', function () {
            return Inertia::render('Administration/General/solicitudesInscripcion', [
                'mustCheckAuth' => true
            ]);
        })->name('solicitudes-inscripcion');

        Route::get('/roles', function () {
            return Inertia::render('Administration/General/roles', [
                'mustCheckAuth' => true
            ]);
        })->name('roles');

        Route::get('/materias', function () {
            return Inertia::render('Administration/General/materias', [
                'mustCheckAuth' => true
            ]);
        })->name('materias');

        Route::get('/informes', function () {
            return Inertia::render('Administration/General/reports', [
                'mustCheckAuth' => true
            ]);
        })->name('informes');

        // Vista provisional: Todas las notificaciones (acceso desde /dashboard/notificaciones)
        Route::get('/notificaciones', function () {
            return Inertia::render('Administration/General/notificaciones', [
                'mustCheckAuth' => true
            ]);
        })->name('notificaciones');

        //Para la parte de las sugerencias del aula
        Route::get('/sugerencia-aula', function () {
            return Inertia::render('Administration/classroomManagement/classroomSuggestion', [
                'mustCheckAuth' => true
            ]);
        })->name('sugerencia-aula');

        Route::get('mantenimientos', function () {
            return Inertia::render('Administration/General/mantenimiento', [
                'mustCheckAuth' => true
            ]);
        })->name('mantenimientos');

        Route::get('incidencias', function() {
            return Inertia::render('Administration/General/incidencia', [
                'mustCheckAuth' => true
            ]);
        })->name('incidencias');
    });

// ===== SISTEMA DE ALIAS PARA COMPATIBILIDAD =====
// Rutas de alias que redirigen a las nuevas URLs con prefijo /dashboard/
Route::middleware(['web', NoBrowserCacheMiddleware::class, 'auth.passport'])->group(function () {
    // Alias de redirección para mantener compatibilidad con URLs antiguas
    Route::redirect('/catalogo', '/dashboard/catalogo', 301);
    Route::redirect('/docentes', '/dashboard/docentes', 301);
    Route::redirect('/estudiantes', '/dashboard/estudiantes', 301);
    Route::redirect('/disponibilidad', '/dashboard/disponibilidad', 301);
    Route::redirect('/departamentos', '/dashboard/departamentos', 301);
    Route::redirect('/informes', '/dashboard/informes', 301);
    Route::redirect('/horarios', '/dashboard/horarios', 301);
    Route::redirect('/grupos', '/dashboard/grupos', 301);
    Route::redirect('/solicitudes-inscripcion', '/dashboard/solicitudes-inscripcion', 301);
    Route::redirect('/roles', '/dashboard/roles', 301);
    Route::redirect('/materias', '/dashboard/materias', 301);
    Route::redirect('/sesiones-clase', '/dashboard/sesiones-clase', 301);
    Route::redirect('/tipos-recursos', '/dashboard/tipos-recursos', 301);
});


//Ruta de prueba para el correo de restablecimiento de contraseña (móvil)
Route::get('/preview-reset-mobile-email', function () {
    $code = '123456';
    $userName = 'Usuario de Prueba';
    return view('emails.reset-password-mobile', compact('code', 'userName'));
});

//Ruta de prueba para la vista blade inscripcion-aprobada.blade.php
Route::get('/preview-inscripcion-aprobada', function () {
    // Datos de ejemplo para la vista
    $usuario = (object)['nombre_completo' => 'Juan Pérez'];
    $mensaje = '¡Tu inscripción ha sido aprobada!';
    $materiaNombre = 'Matemáticas Discretas';
    $grupoNombre = 'Grupo A';
    $docenteNombre = 'Dr. Carlos García';
    $horarios = [
        ['dia' => 'Lunes', 'hora_inicio' => '08:00', 'hora_fin' => '09:30', 'aula' => 'Aula 101'],
        ['dia' => 'Miércoles', 'hora_inicio' => '08:00', 'hora_fin' => '09:30', 'aula' => 'Aula 101'],
    ];

    return view('emails.inscripcion-aprobada', compact('usuario', 'mensaje', 'materiaNombre', 'grupoNombre', 'docenteNombre', 'horarios'));
});


//Ruta de prueba para la vista blade inscripcion-rechazada.blade
Route::get('/preview-inscripcion-rechazada', function () {
    // Datos de ejemplo para la vista
    $usuario = (object)['nombre_completo' => 'María López'];
    $mensaje = 'Tu solicitud de inscripción ha sido rechazada.';
    $materiaNombre = 'Física I';
    $grupoNombre = 'Grupo B';
    $docenteNombre = 'Dra. Ana Torres';
    $motivoRechazo = 'Cupo lleno en el grupo.';

    return view('emails.inscripcion-rechazada', compact('usuario', 'mensaje', 'materiaNombre', 'grupoNombre', 'docenteNombre', 'motivoRechazo'));
});

//Ruta de prueba para la vista blade integridad-datos.blade.php
Route::get('/preview-integridad-datos', function () {
    // Datos de ejemplo para la vista
    $usuario = (object)['nombre_completo' => 'Administrador del Sistema'];
    $mensaje = 'Se ha completado la validación semanal de la integridad de los datos del sistema. A continuación, se presenta un resumen de los hallazgos.';
    $inconsistencias = [
        [
            'severidad' => 'critica',
            'tipo' => 'Registros Huérfanos',
            'tabla' => 'asistencias_estudiantes',
            'registros_afectados' => 5,
            'descripcion' => 'Existen 5 registros de asistencia que apuntan a sesiones de clase que ya no existen en la base de datos.',
        ],
        [
            'severidad' => 'alta',
            'tipo' => 'Inconsistencia de Horarios',
            'tabla' => 'horarios',
            'registros_afectados' => 2,
            'descripcion' => 'Se detectaron 2 horarios con conflictos de solapamiento en la misma aula.',
        ],
        [
            'severidad' => 'media',
            'tipo' => 'Datos Faltantes',
            'tabla' => 'users',
            'registros_afectados' => 12,
            'descripcion' => 'Hay 12 usuarios que no han completado su información de perfil (departamento o carrera).',
        ],
    ];

    return view('emails.integridad-datos', [
        'usuario' => $usuario,
        'mensaje' => $mensaje,
        'totalInconsistencias' => count($inconsistencias),
        'fechaValidacion' => now()->format('d/m/Y H:i:s'),
        'inconsistencias' => $inconsistencias,
    ]);
});

// Ruta de prueba para la vista blade mantenimiento-pendiente.blade.php
Route::get('/preview-mantenimiento-pendiente', function () {
    $usuario = (object)['nombre_completo' => 'Gestor de Mantenimiento'];
    $mensaje = 'Este es un reporte semanal de los mantenimientos pendientes en tu departamento.';
    $departamentoNombre = 'Departamento de Ingeniería';
    $mantenimientos = [
        [
            'aula_nombre' => 'Aula 101',
            'recurso_tipo' => 'Proyector',
            'prioridad' => 'alta',
            'descripcion' => 'El proyector del aula 101 no enciende. Urgente.',
        ],
        [
            'aula_nombre' => 'Laboratorio B',
            'recurso_tipo' => 'Computadora',
            'prioridad' => 'media',
            'descripcion' => 'Una de las computadoras del laboratorio tiene problemas de rendimiento.',
        ],
        [
            'aula_nombre' => 'Sala de Conferencias',
            'recurso_tipo' => 'Aire Acondicionado',
            'prioridad' => 'baja',
            'descripcion' => 'El aire acondicionado hace un ruido extraño, pero funciona.',
        ],
    ];
    $totalPendientes = count($mantenimientos);

    return view('emails.mantenimiento-pendiente', compact('usuario', 'mensaje', 'totalPendientes', 'departamentoNombre', 'mantenimientos'));
});

// Ruta de prueba para la vista blade recordatorio-clase-proxima.blade.php
Route::get('/preview-recordatorio-clase-proxima', function () {
    $usuario = (object)['nombre_completo' => 'Estudiante Ejemplo'];
    $mensaje = 'Tu clase de Matemáticas Discretas está a punto de comenzar.';
    $materiaNombre = 'Matemáticas Discretas';
    $grupoNombre = 'Grupo A';
    $aulaNombre = 'Aula 101';
    $horaInicio = '08:00';
    $horaFin = '09:30';
    $minutosRestantes = 10; // Ejemplo: 10 minutos para que inicie

    return view('emails.recordatorio-clase-proxima', compact('usuario', 'mensaje', 'materiaNombre', 'grupoNombre', 'aulaNombre', 'horaInicio', 'horaFin', 'minutosRestantes'));
});

// Ruta de prueba para la vista blade reporte-estadisticas.blade.php
Route::get('/preview-reporte-estadisticas', function () {
    $usuario = (object)['nombre_completo' => 'Administrador de Estadísticas'];
    $mensaje = 'Aquí tienes el reporte de estadísticas del sistema para el período seleccionado.';
    $periodo = 'Semanal';
    $fechaInicio = now()->subDays(7)->format('d/m/Y');
    $fechaFin = now()->format('d/m/Y');
    $estadisticas = [
        'total_sesiones' => 150,
        'total_asistencias' => 2500,
        'promedio_asistencia' => 85.75,
        'aulas_mas_usadas' => [
            ['nombre' => 'Aula 101', 'sesiones' => 30],
            ['nombre' => 'Laboratorio C', 'sesiones' => 25],
            ['nombre' => 'Aula Magna', 'sesiones' => 20],
            ['nombre' => 'Aula 205', 'sesiones' => 18],
            ['nombre' => 'Laboratorio A', 'sesiones' => 15],
        ],
        'materias_mejor_asistencia' => [
            ['nombre' => 'Cálculo I', 'porcentaje' => 92.10],
            ['nombre' => 'Programación Avanzada', 'porcentaje' => 89.50],
            ['nombre' => 'Bases de Datos', 'porcentaje' => 88.00],
            ['nombre' => 'Redes de Computadoras', 'porcentaje' => 87.20],
            ['nombre' => 'Inteligencia Artificial', 'porcentaje' => 85.00],
        ],
    ];

    return view('emails.reporte-estadisticas', compact('usuario', 'mensaje', 'periodo', 'fechaInicio', 'fechaFin', 'estadisticas'));
});

// Ruta de prueba para la vista blade sesion-no-cerrada.blade.php
Route::get('/preview-sesion-no-cerrada', function () {
    $usuario = (object)['nombre_completo' => 'Docente Prueba'];
    $mensaje = 'Se ha detectado que una de tus sesiones de clase no fue cerrada correctamente.';
    $materiaNombre = 'Física Cuántica';
    $grupoNombre = 'Grupo Z';
    $aulaNombre = 'Laboratorio de Física';
    $fechaClase = now()->subHours(2)->format('d/m/Y');
    $horaInicioReal = now()->subHours(2)->format('H:i');
    $tiempoTranscurrido = 120; // 2 horas

    return view('emails.sesion-no-cerrada', compact('usuario', 'mensaje', 'materiaNombre', 'grupoNombre', 'aulaNombre', 'fechaClase', 'horaInicioReal', 'tiempoTranscurrido'));
});

// Ruta de prueba para la vista blade solicitud-expirada.blade.php
Route::get('/preview-solicitud-expirada', function () {
    $usuario = (object)['nombre_completo' => 'Estudiante Solicitante'];
    $mensaje = 'Lamentamos informarte que tu solicitud de inscripción ha expirado.';
    $diasTranscurridos = 30;
    $materiaNombre = 'Historia del Arte';
    $grupoNombre = 'Grupo Nocturno';
    $fechaSolicitud = now()->subDays(30)->format('d/m/Y');

    return view('emails.solicitud-expirada', compact('usuario', 'mensaje', 'diasTranscurridos', 'materiaNombre', 'grupoNombre', 'fechaSolicitud'));
});

// Ruta de prueba para la vista blade usuario-inactivo-alerta.blade.php
Route::get('/preview-usuario-inactivo-alerta', function () {
    $usuario = (object)['nombre_completo' => 'Usuario Inactivo', 'email' => 'inactivo@example.com'];
    $mensaje = 'Hemos notado que tu cuenta ha estado inactiva por un tiempo.';
    $diasInactivo = 60;
    $diasRestantes = 15;
    $ultimoLogin = now()->subDays(60)->format('d/m/Y H:i:s');
    $fechaEliminacion = now()->addDays(15)->format('d/m/Y');

    return view('emails.usuario-inactivo-alerta', compact('usuario', 'mensaje', 'diasInactivo', 'diasRestantes', 'ultimoLogin', 'fechaEliminacion'));
});

// en routes/web.php


require __DIR__ . '/auth.php';
