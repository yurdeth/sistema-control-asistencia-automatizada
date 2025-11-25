<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Solicitud de Inscripción Recibida
 *
 * Email enviado al DOCENTE cuando un estudiante solicita inscribirse a su grupo.
 *
 * TIMING: Inmediato al crear la solicitud
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     solicitud_id: int,
 *     estudiante_nombre: string,
 *     estudiante_email: string,
 *     grupo_nombre: string,
 *     materia_nombre: string,
 *     fecha_solicitud: string
 *   }
 *
 * @package App\Mail
 */
class SolicitudInscripcionRecibida extends Mailable
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
            subject: 'Nueva Solicitud de Inscripción - ' . ($this->datosAdicionales['materia_nombre'] ?? 'Materia'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-inscripcion-recibida',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'estudianteNombre' => $this->datosAdicionales['estudiante_nombre'] ?? 'N/A',
                'estudianteEmail' => $this->datosAdicionales['estudiante_email'] ?? 'N/A',
                'grupoNombre' => $this->datosAdicionales['grupo_nombre'] ?? 'N/A',
                'materiaNombre' => $this->datosAdicionales['materia_nombre'] ?? 'N/A',
                'fechaSolicitud' => $this->datosAdicionales['fecha_solicitud'] ?? now()->toDateString(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
