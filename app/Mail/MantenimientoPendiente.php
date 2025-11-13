<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Mantenimientos Pendientes
 *
 * Email enviado a GESTORES con reporte de mantenimientos pendientes de aulas/recursos.
 *
 * TIMING: Semanal (viernes a las 9:00 AM)
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     departamento_id: int,
 *     departamento_nombre: string,
 *     mantenimientos_pendientes: array [
 *       {
 *         aula_nombre: string,
 *         recurso_tipo: string,
 *         descripcion: string,
 *         prioridad: string,
 *         fecha_reporte: string
 *       }
 *     ],
 *     total_pendientes: int
 *   }
 *
 * @package App\Mail
 */
class MantenimientoPendiente extends Mailable
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
        $totalPendientes = $this->datosAdicionales['total_pendientes'] ?? 0;

        return new Envelope(
            subject: "Reporte Semanal de Mantenimientos Pendientes ({$totalPendientes} pendientes)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mantenimiento-pendiente',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'departamentoNombre' => $this->datosAdicionales['departamento_nombre'] ?? 'N/A',
                'mantenimientos' => $this->datosAdicionales['mantenimientos_pendientes'] ?? [],
                'totalPendientes' => $this->datosAdicionales['total_pendientes'] ?? 0,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
