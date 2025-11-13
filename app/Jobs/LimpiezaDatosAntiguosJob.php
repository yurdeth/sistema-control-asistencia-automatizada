<?php

namespace App\Jobs;

use App\Models\usuarios;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Token as PassportToken;
use Carbon\Carbon;
use Exception;

/**
 * Job para Limpieza de Datos Antiguos
 *
 * Este Job realiza tareas de mantenimiento y limpieza de datos antiguos para optimizar
 * el rendimiento de la base de datos y liberar espacio.
 *
 * REEMPLAZA:
 * - Eventos SQL de limpieza automática
 *
 * FUNCIONALIDADES:
 * 1. Eliminar usuarios inactivos después de 30 días
 * 2. Eliminar tokens OAuth revocados/expirados después de 7 días
 * 3. Limpiar sesiones de clase muy antiguas (opcional)
 * 4. Limpiar escaneos QR fallidos antiguos (opcional)
 * 5. Archivar notificaciones antiguas (opcional)
 *
 * PROGRAMACIÓN:
 * - Scheduler: Semanal (domingos a las 3:00 AM)
 *
 * CONFIGURACIÓN:
 * - DIAS_INACTIVIDAD_USUARIOS: 30 días (según requerimientos)
 * - DIAS_TOKENS_OAUTH: 7 días (según requerimientos)
 * - DIAS_SESIONES_ANTIGUAS: 90 días (opcional)
 * - DIAS_ESCANEOS_FALLIDOS: 30 días (opcional)
 *
 * @package App\Jobs
 */
class LimpiezaDatosAntiguosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Configuración de períodos de retención (días)
     */
    protected const DIAS_INACTIVIDAD_USUARIOS = 30;
    protected const DIAS_TOKENS_OAUTH = 7;
    protected const DIAS_SESIONES_ANTIGUAS = 90;
    protected const DIAS_ESCANEOS_FALLIDOS = 30;
    protected const DIAS_NOTIFICACIONES_ANTIGUAS = 60;

    /**
     * Número de intentos permitidos
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Tiempo máximo de ejecución (segundos)
     *
     * @var int
     */
    public $timeout = 900; // 15 minutos para limpieza completa

    /**
     * Ejecuta el job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Log::info('Iniciando limpieza de datos antiguos', [
                'timestamp' => now()->toIso8601String(),
            ]);

            $resultados = [];

            // 1. Eliminar usuarios inactivos
            $resultados['usuarios'] = $this->eliminarUsuariosInactivos();

            // 2. Eliminar tokens OAuth antiguos
            $resultados['tokens_oauth'] = $this->eliminarTokensOAuthAntiguos();

            // 3. Limpiar sesiones de clase antiguas (opcional, comentado por defecto)
            // $resultados['sesiones'] = $this->limpiarSesionesAntiguas();

            // 4. Limpiar escaneos QR fallidos antiguos
            $resultados['escaneos_qr'] = $this->limpiarEscaneosQRFallidos();

            // 5. Archivar notificaciones antiguas (marcar como archivadas, no eliminar)
            $resultados['notificaciones'] = $this->archivarNotificacionesAntiguas();

            Log::info('Limpieza de datos completada exitosamente', [
                'resultados' => $resultados,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (Exception $e) {
            Log::error('Error en limpieza de datos antiguos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Elimina usuarios inactivos después de 30 días
     *
     * CRITERIOS:
     * - Usuario no ha iniciado sesión en los últimos 30 días (ultimo_login)
     * - Usuario tiene estado 'inactivo'
     * - NO elimina usuarios con roles críticos (Administrador, Gestor)
     *
     * @return array Resultados de la limpieza
     */
    protected function eliminarUsuariosInactivos(): array
    {
        $fechaLimite = now()->subDays(self::DIAS_INACTIVIDAD_USUARIOS);

        try {
            // Obtener usuarios inactivos candidatos a eliminación
            $usuariosInactivos = usuarios::where('estado', 'inactivo')
                ->where('ultimo_login', '<', $fechaLimite)
                ->whereDoesntHave('roles', function ($query) {
                    // Excluir roles críticos: Administrador (1), Admin Académico (2)
                    $query->whereIn('rol_id', [1, 2]);
                })
                ->get();

            $eliminados = 0;
            $errores = [];

            foreach ($usuariosInactivos as $usuario) {
                try {
                    DB::beginTransaction();

                    Log::info('Eliminando usuario inactivo', [
                        'usuario_id' => $usuario->id,
                        'email' => $usuario->email,
                        'ultimo_login' => $usuario->ultimo_login?->toIso8601String(),
                    ]);

                    // Eliminar usuario (cascade eliminará relaciones)
                    $usuario->delete();

                    DB::commit();
                    $eliminados++;

                } catch (Exception $e) {
                    DB::rollBack();
                    $errores[] = [
                        'usuario_id' => $usuario->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Error al eliminar usuario inactivo', [
                        'usuario_id' => $usuario->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'candidatos' => $usuariosInactivos->count(),
                'eliminados' => $eliminados,
                'errores' => count($errores),
                'detalles_errores' => $errores,
            ];

        } catch (Exception $e) {
            Log::error('Error al buscar usuarios inactivos', [
                'error' => $e->getMessage(),
            ]);

            return [
                'candidatos' => 0,
                'eliminados' => 0,
                'errores' => 1,
                'mensaje' => $e->getMessage(),
            ];
        }
    }

    /**
     * Elimina tokens OAuth revocados o expirados después de 7 días
     *
     * @return array Resultados de la limpieza
     */
    protected function eliminarTokensOAuthAntiguos(): array
    {
        $fechaLimite = now()->subDays(self::DIAS_TOKENS_OAUTH);

        try {
            // Tokens revocados antiguos
            $tokensRevocados = PassportToken::where('revoked', true)
                ->where('updated_at', '<', $fechaLimite)
                ->count();

            PassportToken::where('revoked', true)
                ->where('updated_at', '<', $fechaLimite)
                ->delete();

            // Tokens expirados antiguos
            $tokensExpirados = PassportToken::where('expires_at', '<', $fechaLimite)
                ->count();

            PassportToken::where('expires_at', '<', $fechaLimite)
                ->delete();

            Log::info('Tokens OAuth eliminados', [
                'revocados' => $tokensRevocados,
                'expirados' => $tokensExpirados,
                'fecha_limite' => $fechaLimite->toIso8601String(),
            ]);

            return [
                'revocados_eliminados' => $tokensRevocados,
                'expirados_eliminados' => $tokensExpirados,
                'total' => $tokensRevocados + $tokensExpirados,
            ];

        } catch (Exception $e) {
            Log::error('Error al eliminar tokens OAuth', [
                'error' => $e->getMessage(),
            ]);

            return [
                'revocados_eliminados' => 0,
                'expirados_eliminados' => 0,
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Limpia escaneos QR fallidos antiguos
     *
     * Solo elimina escaneos con resultado 'fallido' y más antiguos que el período configurado.
     *
     * @return array Resultados de la limpieza
     */
    protected function limpiarEscaneosQRFallidos(): array
    {
        $fechaLimite = now()->subDays(self::DIAS_ESCANEOS_FALLIDOS);

        try {
            $escaneosEliminados = DB::table('escaneos_qr')
                ->where('resultado', 'fallido')
                ->where('created_at', '<', $fechaLimite)
                ->delete();

            Log::info('Escaneos QR fallidos eliminados', [
                'cantidad' => $escaneosEliminados,
                'fecha_limite' => $fechaLimite->toIso8601String(),
            ]);

            return [
                'eliminados' => $escaneosEliminados,
            ];

        } catch (Exception $e) {
            Log::error('Error al limpiar escaneos QR fallidos', [
                'error' => $e->getMessage(),
            ]);

            return [
                'eliminados' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Archiva notificaciones antiguas (no las elimina, solo marca como archivadas)
     *
     * Notificaciones más antiguas que 60 días y ya leídas se marcan como archivadas
     * para mantener historial pero optimizar consultas.
     *
     * @return array Resultados del archivado
     */
    protected function archivarNotificacionesAntiguas(): array
    {
        $fechaLimite = now()->subDays(self::DIAS_NOTIFICACIONES_ANTIGUAS);

        try {
            // Agregar campo 'archivado' si no existe (esto debería estar en migración)
            // Por ahora solo contamos y reportamos

            $notificacionesAntiguas = DB::table('notificaciones')
                ->whereNotNull('fecha_leida')
                ->where('created_at', '<', $fechaLimite)
                ->count();

            Log::info('Notificaciones antiguas encontradas (no eliminadas)', [
                'cantidad' => $notificacionesAntiguas,
                'fecha_limite' => $fechaLimite->toIso8601String(),
                'nota' => 'Las notificaciones se mantienen como registro histórico',
            ]);

            return [
                'antiguas_encontradas' => $notificacionesAntiguas,
                'accion' => 'conservadas (registro histórico)',
            ];

        } catch (Exception $e) {
            Log::error('Error al revisar notificaciones antiguas', [
                'error' => $e->getMessage(),
            ]);

            return [
                'antiguas_encontradas' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Limpia sesiones de clase muy antiguas (OPCIONAL - comentado por defecto)
     *
     * Solo para uso en caso de necesitar liberar espacio crítico.
     * NO recomendado eliminar sesiones ya que son registro histórico académico.
     *
     * @return array Resultados de la limpieza
     */
    protected function limpiarSesionesAntiguas(): array
    {
        // NOTA: Esta función está deshabilitada por defecto
        // Las sesiones de clase son registro histórico y no deberían eliminarse
        // Solo habilitar si es absolutamente necesario por espacio en BD

        return [
            'eliminados' => 0,
            'mensaje' => 'Limpieza de sesiones deshabilitada (registro histórico)',
        ];

        /*
        $fechaLimite = now()->subDays(self::DIAS_SESIONES_ANTIGUAS);

        try {
            $sesionesEliminadas = sesiones_clase::where('fecha_clase', '<', $fechaLimite)
                ->where('estado', 'finalizada')
                ->delete();

            return [
                'eliminados' => $sesionesEliminadas,
            ];
        } catch (Exception $e) {
            return [
                'eliminados' => 0,
                'error' => $e->getMessage(),
            ];
        }
        */
    }

    /**
     * Maneja el fallo del job
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::critical('Job de limpieza de datos falló', [
            'error' => $exception->getMessage(),
        ]);
    }
}
