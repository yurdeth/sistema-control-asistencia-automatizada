<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Inscripción Aprobada
 *
 * Email enviado al ESTUDIANTE cuando su solicitud de inscripción es aprobada.
 *
 * TIMING: Inmediato al aprobar la solicitud
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     inscripcion_id: int,
 *     grupo_nombre: string,
 *     materia_nombre: string,
 *     docente_nombre: string,
 *     horarios: array (lista de horarios del grupo)
 *   }
 *
 * @package App\Mail
 */
class InscripcionAprobada extends Mailable
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
            subject: '¡Inscripción Aprobada! - ' . ($this->datosAdicionales['materia_nombre'] ?? 'Materia'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscripcion-aprobada',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'grupoNombre' => $this->datosAdicionales['grupo_nombre'] ?? 'N/A',
                'materiaNombre' => $this->datosAdicionales['materia_nombre'] ?? 'N/A',
                'docenteNombre' => $this->datosAdicionales['docente_nombre'] ?? 'N/A',
                'horarios' => $this->datosAdicionales['horarios'] ?? [],
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
