<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Solicitud de Inscripción Expirada
 *
 * Email enviado al ESTUDIANTE cuando su solicitud de inscripción expira por falta de respuesta.
 *
 * TIMING: Automático después de 30 días sin respuesta del docente
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     solicitud_id: int,
 *     grupo_nombre: string,
 *     materia_nombre: string,
 *     fecha_solicitud: string,
 *     dias_transcurridos: int
 *   }
 *
 * @package App\Mail
 */
class SolicitudExpirada extends Mailable
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
            subject: 'Solicitud de Inscripción Expirada - ' . ($this->datosAdicionales['materia_nombre'] ?? 'Materia'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-expirada',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'grupoNombre' => $this->datosAdicionales['grupo_nombre'] ?? 'N/A',
                'materiaNombre' => $this->datosAdicionales['materia_nombre'] ?? 'N/A',
                'fechaSolicitud' => $this->datosAdicionales['fecha_solicitud'] ?? 'N/A',
                'diasTranscurridos' => $this->datosAdicionales['dias_transcurridos'] ?? 30,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
