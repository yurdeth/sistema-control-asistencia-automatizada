<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Usuario Inactivo - Alerta de Eliminación Próxima
 *
 * Email enviado a USUARIO cuando lleva 25 días inactivo (5 días antes de eliminación automática).
 *
 * TIMING: 25 días de inactividad (5 días antes de eliminación a los 30 días)
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     dias_inactivo: int,
 *     dias_restantes: int,
 *     ultimo_login: string,
 *     fecha_eliminacion_estimada: string
 *   }
 *
 * @package App\Mail
 */
class UsuarioInactivoAlerta extends Mailable
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
        $diasRestantes = $this->datosAdicionales['dias_restantes'] ?? 5;

        return new Envelope(
            subject: "Importante: Tu cuenta será eliminada en {$diasRestantes} días por inactividad",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.usuario-inactivo-alerta',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'diasInactivo' => $this->datosAdicionales['dias_inactivo'] ?? 25,
                'diasRestantes' => $this->datosAdicionales['dias_restantes'] ?? 5,
                'ultimoLogin' => $this->datosAdicionales['ultimo_login'] ?? 'N/A',
                'fechaEliminacion' => $this->datosAdicionales['fecha_eliminacion_estimada'] ?? 'N/A',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
