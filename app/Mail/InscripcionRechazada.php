<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Inscripción Rechazada
 *
 * Email enviado al ESTUDIANTE cuando su solicitud de inscripción es rechazada.
 *
 * TIMING: Inmediato al rechazar la solicitud
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     solicitud_id: int,
 *     grupo_nombre: string,
 *     materia_nombre: string,
 *     motivo_rechazo: string (opcional)
 *   }
 *
 * @package App\Mail
 */
class InscripcionRechazada extends Mailable
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
        return new Envelope(
            subject: 'Solicitud de Inscripción Rechazada - ' . ($this->datosAdicionales['materia_nombre'] ?? 'Materia'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscripcion-rechazada',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'grupoNombre' => $this->datosAdicionales['grupo_nombre'] ?? 'N/A',
                'materiaNombre' => $this->datosAdicionales['materia_nombre'] ?? 'N/A',
                'motivoRechazo' => $this->datosAdicionales['motivo_rechazo'] ?? null,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
