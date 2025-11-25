<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Sesión de Clase No Cerrada
 *
 * Email enviado al DOCENTE cuando una sesión de clase no fue cerrada después del período de gracia.
 *
 * TIMING: Automático después del tiempo de gracia + buffer
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     sesion_id: int,
 *     grupo_nombre: string,
 *     materia_nombre: string,
 *     aula_nombre: string,
 *     fecha_clase: string,
 *     hora_inicio_real: string,
 *     tiempo_transcurrido_minutos: int
 *   }
 *
 * @package App\Mail
 */
class SesionNoCerrada extends Mailable
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
            subject: 'Alerta: Sesión de Clase No Cerrada - ' . ($this->datosAdicionales['materia_nombre'] ?? 'Materia'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sesion-no-cerrada',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'grupoNombre' => $this->datosAdicionales['grupo_nombre'] ?? 'N/A',
                'materiaNombre' => $this->datosAdicionales['materia_nombre'] ?? 'N/A',
                'aulaNombre' => $this->datosAdicionales['aula_nombre'] ?? 'N/A',
                'fechaClase' => $this->datosAdicionales['fecha_clase'] ?? 'N/A',
                'horaInicioReal' => $this->datosAdicionales['hora_inicio_real'] ?? '00:00',
                'tiempoTranscurrido' => $this->datosAdicionales['tiempo_transcurrido_minutos'] ?? 0,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
