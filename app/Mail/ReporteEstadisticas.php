<?php

namespace App\Mail;

use App\Models\notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: Reporte de Estadísticas
 *
 * Email enviado a ADMINISTRADORES y GESTORES con reportes estadísticos periódicos.
 *
 * TIMING: Configurable (diario/semanal/mensual)
 *
 * DATOS ESPERADOS:
 * - notificacion: Modelo de notificacion con relaciones cargadas
 * - datos_adicionales: {
 *     periodo: string (diario|semanal|mensual),
 *     fecha_inicio: string,
 *     fecha_fin: string,
 *     estadisticas: {
 *       total_sesiones: int,
 *       total_asistencias: int,
 *       aulas_mas_usadas: array,
 *       materias_mejor_asistencia: array,
 *       promedio_asistencia: float
 *     },
 *     archivo_adjunto: string|null (path a CSV/PDF)
 *   }
 *
 * @package App\Mail
 */
class ReporteEstadisticas extends Mailable
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
        $periodo = ucfirst($this->datosAdicionales['periodo'] ?? 'Periódico');

        return new Envelope(
            subject: "Reporte de Estadísticas {$periodo} - Sistema de Asistencia",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporte-estadisticas',
            with: [
                'usuario' => $this->notificacion->usuarioDestino,
                'titulo' => $this->notificacion->titulo,
                'mensaje' => $this->notificacion->mensaje,
                'periodo' => $this->datosAdicionales['periodo'] ?? 'diario',
                'fechaInicio' => $this->datosAdicionales['fecha_inicio'] ?? 'N/A',
                'fechaFin' => $this->datosAdicionales['fecha_fin'] ?? 'N/A',
                'estadisticas' => $this->datosAdicionales['estadisticas'] ?? [],
            ],
        );
    }

    public function attachments(): array
    {
        $adjuntos = [];

        // Si hay archivo adjunto (CSV o PDF), agregarlo
        if (isset($this->datosAdicionales['archivo_adjunto']) && file_exists($this->datosAdicionales['archivo_adjunto'])) {
            $adjuntos[] = $this->datosAdicionales['archivo_adjunto'];
        }

        return $adjuntos;
    }
}
