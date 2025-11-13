<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Recordatorio de Clase Próxima
 *
 * Email enviado a docentes y estudiantes para recordarles que tienen una clase próxima.
 *
 * TIMING:
 * - Docentes: 1 hora + 30 minutos antes de la clase
 * - Estudiantes: 15 minutos antes de la clase
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     horario_id: int,
 *     grupo_nombre: string,
 *     materia_nombre: string,
 *     aula_nombre: string,
 *     hora_inicio: string (HH:MM),
 *     hora_fin: string (HH:MM),
 *     minutos_restantes: int
 *   }
 *
 * @package App\Mail
 */
class RecordatorioClaseProxima extends Mailable
{
    use Queueable, SerializesModels;

    public notificaciones $notificacion;
    public array $datosAdicionales;

    /**
     * Constructor
     *
     * @param notificaciones $notificacion
     * @param array $datosAdicionales
     */
    public function __construct(notificaciones $notificacion, array $datosAdicionales = [])
    {
        $this->notificacion = $notificacion;
        $this->datosAdicionales = $datosAdicionales;
    }

    /**
     * Envelope del email (subject, from, etc.)
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: Clase Próxima - ' . ($this->datosAdicionales['materia_nombre'] ?? 'Materia'),
        );
    }

    /**
     * Contenido del email
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-clase-proxima',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'grupoNombre' => $this->datosAdicionales['grupo_nombre'] ?? 'N/A',
                'materiaNombre' => $this->datosAdicionales['materia_nombre'] ?? 'N/A',
                'aulaNombre' => $this->datosAdicionales['aula_nombre'] ?? 'N/A',
                'horaInicio' => $this->datosAdicionales['hora_inicio'] ?? '00:00',
                'horaFin' => $this->datosAdicionales['hora_fin'] ?? '00:00',
                'minutosRestantes' => $this->datosAdicionales['minutos_restantes'] ?? 0,
            ],
        );
    }

    /**
     * Adjuntos (si hay)
     */
    public function attachments(): array
    {
        return [];
    }
}
