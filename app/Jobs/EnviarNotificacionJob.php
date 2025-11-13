<?php

namespace App\Jobs;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Job para Enviar Notificaciones
 *
 * Este Job se encarga del envío real de notificaciones por email.
 * Sigue la arquitectura dual del sistema: BD + Email.
 *
 * FLUJO:
 * 1. Recibe ID de notificación creada en BD (ya existe el registro)
 * 2. Obtiene datos del usuario destinatario y tipo de notificación
 * 3. Envía email vía Mailgun usando el Mailable correspondiente
 * 4. Actualiza estado en BD (enviada/fallida)
 * 5. Loguea resultado (éxito/error)
 *
 * USO:
 * - Puede ejecutarse de forma síncrona (sin queue): dispatch_sync()
 * - O asíncrona (con queue): dispatch()
 *
 * CONFIGURACIÓN:
 * - Por defecto: ShouldQueue (asíncrono)
 * - Para envío inmediato: usar dispatch_sync() al llamar
 *
 * @package App\Jobs
 */
class EnviarNotificacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ID de la notificación a enviar
     */
    protected int $notificacionId;

    /**
     * Número de intentos permitidos
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Tiempo de espera antes de reintentar (segundos)
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Tiempo máximo de ejecución (segundos)
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Constructor
     *
     * @param int $notificacionId ID de la notificación en BD
     */
    public function __construct(int $notificacionId)
    {
        $this->notificacionId = $notificacionId;
    }

    /**
     * Ejecuta el job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // 1. Obtener notificación con relaciones
            $notificacion = notificaciones::with(['tipoNotificacion', 'usuarioDestino'])
                ->findOrFail($this->notificacionId);

            Log::info('Iniciando envío de notificación', [
                'notificacion_id' => $notificacion->id,
                'tipo' => $notificacion->tipoNotificacion->nombre,
                'usuario_destino' => $notificacion->usuarioDestino->email,
                'canal' => $notificacion->canal,
            ]);

            // 2. Validar que el usuario tiene email válido
            if (!$notificacion->usuarioDestino->email || !filter_var($notificacion->usuarioDestino->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Usuario {$notificacion->usuarioDestino->id} no tiene email válido");
            }

            // 3. Enviar por el canal especificado
            if ($notificacion->canal === 'email') {
                $this->enviarPorEmail($notificacion);
            } elseif ($notificacion->canal === 'push') {
                // TODO: Implementar envío por push notifications cuando esté disponible (cuando lo implemente xd)
                Log::info('Push notifications no implementadas aún, solo se registra en BD', [
                    'notificacion_id' => $notificacion->id,
                ]);
                $notificacion->marcarComoEnviada();
            }

            Log::info('Notificación enviada exitosamente', [
                'notificacion_id' => $notificacion->id,
                'tipo' => $notificacion->tipoNotificacion->nombre,
                'usuario_destino' => $notificacion->usuarioDestino->email,
            ]);

        } catch (Exception $e) {
            Log::error('Error al enviar notificación', [
                'notificacion_id' => $this->notificacionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Marcar como fallida en BD
            try {
                $notificacion = notificaciones::find($this->notificacionId);
                if ($notificacion) {
                    $notificacion->marcarComoFallida($e->getMessage());
                }
            } catch (Exception $updateException) {
                Log::error('Error al actualizar estado de notificación fallida', [
                    'notificacion_id' => $this->notificacionId,
                    'error' => $updateException->getMessage(),
                ]);
            }

            // Relanzar excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Envía la notificación por email usando el Mailable correspondiente
     *
     * @param notificaciones $notificacion
     * @return void
     * @throws Exception
     */
    protected function enviarPorEmail(notificaciones $notificacion): void
    {
        $tipoNombre = $notificacion->tipoNotificacion->nombre;
        $usuario = $notificacion->usuarioDestino;
        $datosAdicionales = $notificacion->datos_adicionales ?? [];

        // Determinar el Mailable a usar según el tipo de notificación
        $mailableClass = $this->obtenerMailableClass($tipoNombre);

        if (!$mailableClass || !class_exists($mailableClass)) {
            throw new Exception("No existe Mailable para tipo de notificación: {$tipoNombre}");
        }

        // Construir el Mailable con los datos necesarios
        $mailable = new $mailableClass($notificacion, $datosAdicionales);

        // Enviar email
        Mail::to($usuario->email)->send($mailable);

        // Marcar como enviada en BD
        $notificacion->marcarComoEnviada();
    }

    /**
     * Obtiene la clase Mailable correspondiente según el tipo de notificación
     *
     * @param string $tipoNombre Nombre del tipo de notificación
     * @return string|null Nombre completo de la clase Mailable
     */
    protected function obtenerMailableClass(string $tipoNombre): ?string
    {
        // Mapeo de tipos de notificación a clases Mailable
        $mapeo = [
            'clase_proxima_docente' => \App\Mail\RecordatorioClaseProxima::class,
            'clase_proxima_estudiante' => \App\Mail\RecordatorioClaseProxima::class,
            'solicitud_inscripcion' => \App\Mail\SolicitudInscripcionRecibida::class,
            'inscripcion_aprobada' => \App\Mail\InscripcionAprobada::class,
            'inscripcion_rechazada' => \App\Mail\InscripcionRechazada::class,
            'solicitud_expirada' => \App\Mail\SolicitudExpirada::class,
            'sesion_no_cerrada' => \App\Mail\SesionNoCerrada::class,
            'mantenimiento_pendiente' => \App\Mail\MantenimientoPendiente::class,
            'integridad_datos' => \App\Mail\IntegridadDatos::class,
            'usuario_inactivo_alerta' => \App\Mail\UsuarioInactivoAlerta::class,
            'reporte_estadisticas' => \App\Mail\ReporteEstadisticas::class,
        ];

        return $mapeo[$tipoNombre] ?? null;
    }

    /**
     * Maneja el fallo del job después de todos los reintentos
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::critical('Job de notificación falló después de todos los reintentos', [
            'notificacion_id' => $this->notificacionId,
            'error' => $exception->getMessage(),
            'intentos' => $this->tries,
        ]);

        // Marcar como fallida en BD
        try {
            $notificacion = notificaciones::find($this->notificacionId);
            if ($notificacion) {
                $notificacion->marcarComoFallida(
                    "Falló después de {$this->tries} intentos: " . $exception->getMessage()
                );
            }
        } catch (Exception $e) {
            Log::error('Error al marcar notificación como fallida después de reintentos', [
                'notificacion_id' => $this->notificacionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
