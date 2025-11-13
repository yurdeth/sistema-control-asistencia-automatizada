<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Validación de Integridad de Datos
 *
 * Email enviado a ADMINISTRADORES con reporte de inconsistencias detectadas en la base de datos.
 *
 * TIMING: Semanal (domingos a las 3:00 AM) - solo si hay inconsistencias
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     inconsistencias: array [
 *       {
 *         tipo: string,
 *         descripcion: string,
 *         tabla: string,
 *         registros_afectados: int,
 *         severidad: string (baja|media|alta|critica)
 *       }
 *     ],
 *     total_inconsistencias: int,
 *     fecha_validacion: string
 *   }
 *
 * @package App\Mail
 */
class IntegridadDatos extends Mailable
{
    use Queueable, SerializesModels;

    public notificaciones $notificacion;
    public array $datosAdicionales;

    public function __construct(notificaciones $notificacion, array $datosAdicionales = [])
    {
        $this->notificacion = $notificacion;
        $this->datosAdicionales = $datosAdicionales;
    }

    public function envelope(): Envelope
    {
        $totalInconsistencias = $this->datosAdicionales['total_inconsistencias'] ?? 0;

        return new Envelope(
            subject: "Alerta: Inconsistencias en Base de Datos ({$totalInconsistencias} detectadas)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.integridad-datos',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'inconsistencias' => $this->datosAdicionales['inconsistencias'] ?? [],
                'totalInconsistencias' => $this->datosAdicionales['total_inconsistencias'] ?? 0,
                'fechaValidacion' => $this->datosAdicionales['fecha_validacion'] ?? now()->toDateString(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
