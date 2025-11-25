<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerarEstadisticasDiariasJob;
use App\Jobs\LimpiezaDatosAntiguosJob;
use App\Jobs\EnviarNotificacionJob;
use App\Models\horarios;
use App\Models\sesiones_clase;
use App\Models\solicitudes_inscripcion;
use App\Models\notificaciones;
use App\Models\tipos_notificacion;
use Carbon\Carbon;

// ========================================
// COMANDO DE EJEMPLO (mantener para testing)
// ========================================
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// SCHEDULERS AUTOMÁTICOS
// Reemplazan eventos SQL (evt_*)
// ========================================

/**
 * 1. RECORDATORIOS DE CLASE PARA DOCENTES
 * Timing: 1 hora + 30 minutos antes de la clase
 * Ejecuta cada 10 minutos para capturar horarios próximos
 */
Schedule::call(function () {
    try {
        $ahora = now();
        $tiempoAntes = 90; // 90 minutos = 1 hora + 30 min

        // Buscar horarios que inicien en aproximadamente 90 minutos
        $proximasClases = horarios::with(['grupo.usuario', 'grupo.materia', 'aula'])
            ->where('dia_semana', $ahora->dayOfWeek)
            ->where('estado', 'activo')
            ->get()
            ->filter(function ($horario) use ($ahora, $tiempoAntes) {
                $horaClase = Carbon::createFromFormat('H:i:s', $horario->hora_inicio);
                $horaClase->setDateFrom($ahora);

                $diferencia = $ahora->diffInMinutes($horaClase, false);
                // Capturar clases entre 85 y 95 minutos (ventana de 10 min)
                return $diferencia >= 85 && $diferencia <= 95;
            });

        foreach ($proximasClases as $horario) {
            // Verificar que el docente exista
            if (!$horario->grupo || !$horario->grupo->usuario) {
                continue;
            }

            // Obtener o crear tipo de notificación
            $tipoNotificacion = tipos_notificacion::firstOrCreate(
                ['nombre' => 'clase_proxima_docente'],
                [
                    'descripcion' => 'Recordatorio de clase próxima para docente',
                    'prioridad' => 'alta',
                    'estado' => 'activo',
                ]
            );

            $minutosRestantes = Carbon::createFromFormat('H:i:s', $horario->hora_inicio)
                ->setDateFrom($ahora)
                ->diffInMinutes($ahora);

            // Crear notificación en BD
            $notificacion = notificaciones::create([
                'tipo_notificacion_id' => $tipoNotificacion->id,
                'usuario_destino_id' => $horario->grupo->usuario_id,
                'titulo' => 'Clase Próxima en 90 minutos',
                'mensaje' => "Tu clase de {$horario->grupo->materia->nombre} inicia en aproximadamente 90 minutos en {$horario->aula->nombre}.",
                'canal' => 'email',
                'estado' => 'pendiente',
                'datos_adicionales' => json_encode([
                    'horario_id' => $horario->id,
                    'grupo_nombre' => $horario->grupo->nombre,
                    'materia_nombre' => $horario->grupo->materia->nombre,
                    'aula_nombre' => $horario->aula->nombre,
                    'hora_inicio' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'minutos_restantes' => $minutosRestantes,
                ]),
            ]);

            // Enviar email inmediatamente (sin queue)
            EnviarNotificacionJob::dispatch($notificacion->id);

            Log::info('Recordatorio de clase enviado a docente', [
                'horario_id' => $horario->id,
                'docente_id' => $horario->grupo->usuario_id,
                'materia' => $horario->grupo->materia->nombre,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error en scheduler de recordatorios para docentes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
})->everyTenMinutes()->name('recordatorios-clase-docentes')->description('Enviar recordatorios de clase a docentes (90 min antes)');

/**
 * 2. RECORDATORIOS DE CLASE PARA ESTUDIANTES
 * Timing: 15 minutos antes de la clase
 * Ejecuta cada 5 minutos para capturar horarios próximos
 */
Schedule::call(function () {
    try {
        $ahora = now();
        $tiempoAntes = 15; // 15 minutos

        // Buscar horarios que inicien en aproximadamente 15 minutos
        $proximasClases = horarios::with(['grupo.inscripciones.estudiante', 'grupo.materia', 'aula'])
            ->where('dia_semana', $ahora->dayOfWeek)
            ->where('estado', 'activo')
            ->get()
            ->filter(function ($horario) use ($ahora, $tiempoAntes) {
                $horaClase = Carbon::createFromFormat('H:i:s', $horario->hora_inicio);
                $horaClase->setDateFrom($ahora);

                $diferencia = $ahora->diffInMinutes($horaClase, false);
                // Capturar clases entre 12 y 18 minutos (ventana de 6 min para ejecución cada 5 min)
                return $diferencia >= 12 && $diferencia <= 18;
            });

        foreach ($proximasClases as $horario) {
            // Notificar a todos los estudiantes inscritos
            foreach ($horario->grupo->inscripciones as $inscripcion) {
                if (!$inscripcion->estudiante) {
                    continue;
                }

                // Obtener o crear tipo de notificación
                $tipoNotificacion = tipos_notificacion::firstOrCreate(
                    ['nombre' => 'clase_proxima_estudiante'],
                    [
                        'descripcion' => 'Recordatorio de clase próxima para estudiante',
                        'prioridad' => 'alta',
                        'estado' => 'activo',
                    ]
                );

                $minutosRestantes = Carbon::createFromFormat('H:i:s', $horario->hora_inicio)
                    ->setDateFrom($ahora)
                    ->diffInMinutes($ahora);

                // Crear notificación en BD
                $notificacion = notificaciones::create([
                    'tipo_notificacion_id' => $tipoNotificacion->id,
                    'usuario_destino_id' => $inscripcion->estudiante_id,
                    'titulo' => 'Clase Próxima en 15 minutos',
                    'mensaje' => "Tu clase de {$horario->grupo->materia->nombre} inicia en aproximadamente 15 minutos en {$horario->aula->nombre}.",
                    'canal' => 'email',
                    'estado' => 'pendiente',
                    'datos_adicionales' => json_encode([
                        'horario_id' => $horario->id,
                        'grupo_nombre' => $horario->grupo->nombre,
                        'materia_nombre' => $horario->grupo->materia->nombre,
                        'aula_nombre' => $horario->aula->nombre,
                        'hora_inicio' => $horario->hora_inicio,
                        'hora_fin' => $horario->hora_fin,
                        'minutos_restantes' => $minutosRestantes,
                    ]),
                ]);

                // Enviar email inmediatamente
                EnviarNotificacionJob::dispatch($notificacion->id);
            }

            Log::info('Recordatorios de clase enviados a estudiantes', [
                'horario_id' => $horario->id,
                'estudiantes_notificados' => $horario->grupo->inscripciones->count(),
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error en scheduler de recordatorios para estudiantes', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
})->everyFiveMinutes()->name('recordatorios-clase-estudiantes')->description('Enviar recordatorios de clase a estudiantes (15 min antes)');

/**
 * 3. CERRAR SESIONES AUTOMÁTICAMENTE
 * Reemplaza: evt_cerrar_sesiones_automaticamente
 * Timing: Cada 30 minutos
 * Cierra sesiones que superaron el período de gracia (2 horas desde hora_inicio_real)
 */
Schedule::call(function () {
    try {
        $tiempoGracia = 120; // 2 horas en minutos
        $limiteTiempo = now()->subMinutes($tiempoGracia);

        // Buscar sesiones en curso que superaron el tiempo de gracia
        $sesionesAbiertas = sesiones_clase::with(['horario.grupo.usuario', 'horario.aula'])
            ->where('estado', 'en_curso')
            ->where('hora_inicio_real', '<', $limiteTiempo)
            ->get();

        foreach ($sesionesAbiertas as $sesion) {
            DB::beginTransaction();
            try {
                // Cerrar sesión automáticamente
                $sesion->update([
                    'estado' => 'finalizada',
                    'hora_fin_real' => now(),
                    // duracion_minutos y retraso_minutos se calculan automáticamente por SesionClaseObserver
                ]);

                // Liberar aula
                if ($sesion->horario && $sesion->horario->aula) {
                    $sesion->horario->aula->update(['estado' => 'disponible']);
                }

                // Notificar al docente
                if ($sesion->horario && $sesion->horario->grupo && $sesion->horario->grupo->usuario) {
                    $tipoNotificacion = tipos_notificacion::firstOrCreate(
                        ['nombre' => 'sesion_no_cerrada'],
                        [
                            'descripcion' => 'Alerta de sesión no cerrada a tiempo',
                            'prioridad' => 'media',
                            'estado' => 'activo',
                        ]
                    );

                    $tiempoTranscurrido = $sesion->hora_inicio_real->diffInMinutes(now());

                    $notificacion = notificaciones::create([
                        'tipo_notificacion_id' => $tipoNotificacion->id,
                        'usuario_destino_id' => $sesion->horario->grupo->usuario_id,
                        'titulo' => 'Sesión Cerrada Automáticamente',
                        'mensaje' => "La sesión de {$sesion->horario->grupo->materia->nombre} fue cerrada automáticamente porque superó el período de gracia de {$tiempoGracia} minutos.",
                        'canal' => 'email',
                        'estado' => 'pendiente',
                        'datos_adicionales' => json_encode([
                            'sesion_id' => $sesion->id,
                            'grupo_nombre' => $sesion->horario->grupo->nombre,
                            'materia_nombre' => $sesion->horario->grupo->materia->nombre,
                            'aula_nombre' => $sesion->horario->aula->nombre,
                            'fecha_clase' => $sesion->fecha_clase->toDateString(),
                            'hora_inicio_real' => $sesion->hora_inicio_real->format('H:i'),
                            'tiempo_transcurrido_minutos' => $tiempoTranscurrido,
                        ]),
                    ]);

                    EnviarNotificacionJob::dispatch($notificacion->id);
                }

                DB::commit();

                Log::info('Sesión cerrada automáticamente', [
                    'sesion_id' => $sesion->id,
                    'tiempo_transcurrido' => $sesion->hora_inicio_real->diffInMinutes(now()) . ' minutos',
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al cerrar sesión automáticamente', [
                    'sesion_id' => $sesion->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    } catch (\Exception $e) {
        Log::error('Error en scheduler de cierre automático de sesiones', [
            'error' => $e->getMessage(),
        ]);
    }
})->everyThirtyMinutes()->name('cerrar-sesiones-automaticamente')->description('Cerrar sesiones que superaron el período de gracia');

/**
 * 4. EXPIRAR SOLICITUDES DE INSCRIPCIÓN
 * Timing: Diario a las 1:00 AM
 * Expira solicitudes pendientes después de 30 días
 */
Schedule::call(function () {
    try {
        $diasExpiracion = 30;
        $fechaLimite = now()->subDays($diasExpiracion);

        // Buscar solicitudes pendientes antiguas
        $solicitudesExpiradas = solicitudes_inscripcion::with(['estudiante', 'grupo.materia'])
            ->where('estado', 'pendiente')
            ->where('created_at', '<', $fechaLimite)
            ->get();

        foreach ($solicitudesExpiradas as $solicitud) {
            DB::beginTransaction();
            try {
                // Marcar como expirada
                $solicitud->update([
                    'estado' => 'expirada',
                ]);

                // Notificar al estudiante
                if ($solicitud->estudiante) {
                    $tipoNotificacion = tipos_notificacion::firstOrCreate(
                        ['nombre' => 'solicitud_expirada'],
                        [
                            'descripcion' => 'Notificación de solicitud de inscripción expirada',
                            'prioridad' => 'baja',
                            'estado' => 'activo',
                        ]
                    );

                    $diasTranscurridos = $solicitud->created_at->diffInDays(now());

                    $notificacion = notificaciones::create([
                        'tipo_notificacion_id' => $tipoNotificacion->id,
                        'usuario_destino_id' => $solicitud->estudiante_id,
                        'titulo' => 'Solicitud de Inscripción Expirada',
                        'mensaje' => "Tu solicitud de inscripción para {$solicitud->grupo->materia->nombre} expiró después de {$diasTranscurridos} días sin respuesta.",
                        'canal' => 'email',
                        'estado' => 'pendiente',
                        'datos_adicionales' => json_encode([
                            'solicitud_id' => $solicitud->id,
                            'grupo_nombre' => $solicitud->grupo->nombre,
                            'materia_nombre' => $solicitud->grupo->materia->nombre,
                            'fecha_solicitud' => $solicitud->created_at->toDateString(),
                            'dias_transcurridos' => $diasTranscurridos,
                        ]),
                    ]);

                    EnviarNotificacionJob::dispatch($notificacion->id);
                }

                DB::commit();

                Log::info('Solicitud de inscripción expirada', [
                    'solicitud_id' => $solicitud->id,
                    'estudiante_id' => $solicitud->estudiante_id,
                    'dias_transcurridos' => $solicitud->created_at->diffInDays(now()),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al expirar solicitud de inscripción', [
                    'solicitud_id' => $solicitud->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    } catch (\Exception $e) {
        Log::error('Error en scheduler de expiración de solicitudes', [
            'error' => $e->getMessage(),
        ]);
    }
})->dailyAt('01:00')->name('expirar-solicitudes-inscripcion')->description('Expirar solicitudes de inscripción pendientes después de 30 días');

/**
 * 5. GENERAR ESTADÍSTICAS DIARIAS
 * Timing: Diario a las 2:00 AM
 * Ejecuta el Job de estadísticas diarias
 */
Schedule::job(new GenerarEstadisticasDiariasJob(now()->subDay()))
    ->dailyAt('02:00')
    ->name('generar-estadisticas-diarias')
    ->description('Generar estadísticas diarias de uso de aulas y asistencias');

/**
 * 6. LIMPIEZA DE DATOS ANTIGUOS
 * Timing: Semanal (domingos a las 3:00 AM)
 * Ejecuta el Job de limpieza de datos
 */
Schedule::job(new LimpiezaDatosAntiguosJob())
    ->weeklyOn(0, '03:00') // 0 = Domingo
    ->name('limpieza-datos-antiguos')
    ->description('Eliminar usuarios inactivos, tokens OAuth antiguos y datos obsoletos');

/**
 * 7. VALIDACIÓN SEMANAL DE INTEGRIDAD
 * Timing: Semanal (domingos a las 4:00 AM)
 * Valida integridad de datos y envía reporte si hay inconsistencias
 */
Schedule::call(function () {
    try {
        $inconsistencias = [];

        // Validación 1: Grupos con contador incorrecto de inscritos
        $gruposIncorrectos = DB::select("
            SELECT g.id, g.nombre, g.estudiantes_inscritos,
                   COUNT(i.id) as real_inscritos
            FROM grupos g
            LEFT JOIN inscripciones i ON g.id = i.grupo_id
            GROUP BY g.id, g.nombre, g.estudiantes_inscritos
            HAVING g.estudiantes_inscritos != COUNT(i.id)
        ");

        if (count($gruposIncorrectos) > 0) {
            $inconsistencias[] = [
                'tipo' => 'contador_inscritos_incorrecto',
                'descripcion' => 'Grupos con contador de estudiantes inscritos que no coincide con la realidad',
                'tabla' => 'grupos',
                'registros_afectados' => count($gruposIncorrectos),
                'severidad' => 'media',
            ];
        }

        // Validación 2: Sesiones sin duracion_minutos después de ser finalizadas
        $sesionesIncompletas = sesiones_clase::where('estado', 'finalizada')
            ->whereNull('duracion_minutos')
            ->count();

        if ($sesionesIncompletas > 0) {
            $inconsistencias[] = [
                'tipo' => 'sesiones_sin_duracion',
                'descripcion' => 'Sesiones finalizadas sin duración calculada',
                'tabla' => 'sesiones_clase',
                'registros_afectados' => $sesionesIncompletas,
                'severidad' => 'baja',
            ];
        }

        // Si hay inconsistencias, notificar a administradores
        if (!empty($inconsistencias)) {
            $admins = DB::table('usuario_roles')
                ->join('users', 'usuario_roles.usuario_id', '=', 'users.id')
                ->where('usuario_roles.rol_id', 1) // Rol Administrador
                ->select('users.*')
                ->get();

            foreach ($admins as $admin) {
                $tipoNotificacion = tipos_notificacion::firstOrCreate(
                    ['nombre' => 'integridad_datos'],
                    [
                        'descripcion' => 'Alerta de inconsistencias en base de datos',
                        'prioridad' => 'alta',
                        'estado' => 'activo',
                    ]
                );

                $notificacion = notificaciones::create([
                    'tipo_notificacion_id' => $tipoNotificacion->id,
                    'usuario_destino_id' => $admin->id,
                    'titulo' => 'Alerta: Inconsistencias en Base de Datos',
                    'mensaje' => 'Se detectaron ' . count($inconsistencias) . ' inconsistencias en la validación semanal de integridad de datos.',
                    'canal' => 'email',
                    'estado' => 'pendiente',
                    'datos_adicionales' => json_encode([
                        'inconsistencias' => $inconsistencias,
                        'total_inconsistencias' => count($inconsistencias),
                        'fecha_validacion' => now()->toDateString(),
                    ]),
                ]);

                EnviarNotificacionJob::dispatch($notificacion->id);
            }

            Log::warning('Inconsistencias detectadas en validación de integridad', [
                'total' => count($inconsistencias),
                'inconsistencias' => $inconsistencias,
            ]);
        } else {
            Log::info('Validación de integridad completada sin inconsistencias');
        }
    } catch (\Exception $e) {
        Log::error('Error en scheduler de validación de integridad', [
            'error' => $e->getMessage(),
        ]);
    }
})->weeklyOn(0, '04:00')->name('validacion-integridad-datos')->description('Validar integridad de datos y enviar reporte si hay inconsistencias');

/**
 * 8. ALERTAS DE MANTENIMIENTO PENDIENTES
 * Timing: Semanal (viernes a las 9:00 AM)
 * Envía reporte de mantenimientos pendientes a gestores
 */
Schedule::call(function () {
    try {
        // Buscar mantenimientos pendientes agrupados por departamento
        $mantenimientosPorDepartamento = DB::table('mantenimientos as m')
            ->join('aulas as a', 'm.aula_id', '=', 'a.id')
            ->join('recursos_tipo as rt', 'm.recurso_tipo_id', '=', 'rt.id')
            ->join('departamentos as d', 'a.departamento_id', '=', 'd.id')
            ->where('m.estado', 'pendiente')
            ->select(
                'd.id as departamento_id',
                'd.nombre as departamento_nombre',
                'd.gestor_id',
                'a.nombre as aula_nombre',
                'rt.nombre as recurso_tipo',
                'm.descripcion',
                'm.prioridad',
                'm.created_at as fecha_reporte'
            )
            ->get()
            ->groupBy('departamento_id');

        foreach ($mantenimientosPorDepartamento as $departamentoId => $mantenimientos) {
            $departamento = $mantenimientos->first();
            $gestorId = $departamento->gestor_id;

            if (!$gestorId) {
                continue; // Sin gestor asignado
            }

            $tipoNotificacion = tipos_notificacion::firstOrCreate(
                ['nombre' => 'mantenimiento_pendiente'],
                [
                    'descripcion' => 'Reporte semanal de mantenimientos pendientes',
                    'prioridad' => 'media',
                    'estado' => 'activo',
                ]
            );

            $mantenimientosArray = $mantenimientos->map(function ($m) {
                return [
                    'aula_nombre' => $m->aula_nombre,
                    'recurso_tipo' => $m->recurso_tipo,
                    'descripcion' => $m->descripcion,
                    'prioridad' => $m->prioridad,
                    'fecha_reporte' => Carbon::parse($m->fecha_reporte)->toDateString(),
                ];
            })->toArray();

            $notificacion = notificaciones::create([
                'tipo_notificacion_id' => $tipoNotificacion->id,
                'usuario_destino_id' => $gestorId,
                'titulo' => 'Reporte Semanal de Mantenimientos Pendientes',
                'mensaje' => "Hay {$mantenimientos->count()} mantenimiento(s) pendiente(s) en {$departamento->departamento_nombre}.",
                'canal' => 'email',
                'estado' => 'pendiente',
                'datos_adicionales' => json_encode([
                    'departamento_id' => $departamentoId,
                    'departamento_nombre' => $departamento->departamento_nombre,
                    'mantenimientos_pendientes' => $mantenimientosArray,
                    'total_pendientes' => $mantenimientos->count(),
                ]),
            ]);

            EnviarNotificacionJob::dispatch($notificacion->id);

            Log::info('Reporte de mantenimientos enviado', [
                'departamento_id' => $departamentoId,
                'gestor_id' => $gestorId,
                'total_pendientes' => $mantenimientos->count(),
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error en scheduler de alertas de mantenimiento', [
            'error' => $e->getMessage(),
        ]);
    }
})->weeklyOn(5, '09:00')->name('alertas-mantenimiento-pendientes')->description('Enviar reporte semanal de mantenimientos pendientes a gestores');
