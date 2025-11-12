<?php

namespace App\Observers;

use App\Models\sesiones_clase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Observer para el modelo SesionClase
 *
 * Reemplaza el trigger SQL: trg_calcular_duracion_y_retraso
 *
 * Calcula automáticamente:
 * - duracion_minutos: Tiempo total de la clase
 * - retraso_minutos: Diferencia entre hora_inicio_programada y hora_inicio_real
 *
 * @package App\Observers
 * @version 1.0.0
 */
class SesionClaseObserver
{
    /**
     * Evento: Antes de guardar la sesión (crear o actualizar)
     *
     * Calcula duración y retraso automáticamente
     *
     * @param sesiones_clase $sesion
     * @return void
     */
    public function saving(sesiones_clase $sesion): void
    {
        try {
            // Calcular RETRASO si se acaba de establecer hora_inicio_real
            if ($sesion->hora_inicio_real && $sesion->isDirty('hora_inicio_real')) {
                $sesion->retraso_minutos = $this->calcularRetraso($sesion);
            }

            // Calcular DURACIÓN si se acaba de establecer hora_fin_real
            if ($sesion->hora_inicio_real && $sesion->hora_fin_real && $sesion->isDirty('hora_fin_real')) {
                $sesion->duracion_minutos = $this->calcularDuracion($sesion);
            }

            Log::debug('Cálculos automáticos de sesión', [
                'sesion_id' => $sesion->id,
                'duracion_minutos' => $sesion->duracion_minutos,
                'retraso_minutos' => $sesion->retraso_minutos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al calcular duración/retraso', [
                'sesion_id' => $sesion->id ?? 'nuevo',
                'error' => $e->getMessage()
            ]);

            // No lanzar excepción para no bloquear el guardado
            // pero loguear el error
        }
    }

    /**
     * Calcular el retraso en minutos
     *
     * Compara hora_inicio_real con hora_inicio del horario
     *
     * @param sesiones_clase $sesion
     * @return int Minutos de retraso (0 si empezó antes o a tiempo)
     */
    private function calcularRetraso(sesiones_clase $sesion): int
    {
        try {
            // Obtener horario con la hora programada
            $horario = $sesion->horario;

            if (!$horario || !$horario->hora_inicio) {
                return 0;
            }

            // Crear Carbon instances para comparación
            $horaInicioProgramada = Carbon::createFromFormat('H:i:s', $horario->hora_inicio);
            $horaInicioReal = Carbon::parse($sesion->hora_inicio_real);

            // Calcular diferencia en minutos
            $diferencia = $horaInicioReal->diffInMinutes($horaInicioProgramada, false);

            // Si la diferencia es negativa, significa que empezó antes (sin retraso)
            // Si es positiva, es el retraso en minutos
            return max(0, (int)$diferencia);

        } catch (\Exception $e) {
            Log::warning('Error al calcular retraso', [
                'sesion_id' => $sesion->id,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Calcular la duración en minutos
     *
     * Diferencia entre hora_fin_real y hora_inicio_real
     *
     * @param sesiones_clase $sesion
     * @return int|null Minutos de duración
     */
    private function calcularDuracion(sesiones_clase $sesion): ?int
    {
        try {
            if (!$sesion->hora_inicio_real || !$sesion->hora_fin_real) {
                return null;
            }

            $inicio = Carbon::parse($sesion->hora_inicio_real);
            $fin = Carbon::parse($sesion->hora_fin_real);

            // Calcular diferencia en minutos (siempre positivo)
            $duracion = $inicio->diffInMinutes($fin);

            return (int)$duracion;

        } catch (\Exception $e) {
            Log::warning('Error al calcular duración', [
                'sesion_id' => $sesion->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Evento: Después de actualizar la sesión
     *
     * Loguear cambios importantes
     *
     * @param sesiones_clase $sesion
     * @return void
     */
    public function updated(sesiones_clase $sesion): void
    {
        // Log de cambios importantes
        $cambios = $sesion->getChanges();

        if (!empty($cambios)) {
            Log::info('Sesión de clase actualizada', [
                'sesion_id' => $sesion->id,
                'cambios' => array_keys($cambios),
                'estado' => $sesion->estado
            ]);
        }
    }
}
