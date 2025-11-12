<?php

namespace App\Observers;

use App\Models\inscripciones;
use Illuminate\Support\Facades\Log;

/**
 * Observer para el modelo Inscripcion
 *
 * Reemplaza los triggers SQL:
 * - trg_incrementar_inscritos (al crear inscripción)
 * - trg_decrementar_inscritos (al eliminar inscripción)
 *
 * Mantiene sincronizado el contador de estudiantes_inscritos en la tabla grupos
 *
 * @package App\Observers
 * @version 1.0.0
 */
class InscripcionObserver
{
    /**
     * Evento: Después de crear una inscripción
     *
     * Incrementa el contador estudiantes_inscritos del grupo
     *
     * @param inscripciones $inscripcion
     * @return void
     */
    public function created(inscripciones $inscripcion): void
    {
        try {
            // Incrementar contador de estudiantes inscritos
            $inscripcion->grupo()->increment('estudiantes_inscritos');

            Log::debug('Contador de inscritos incrementado', [
                'inscripcion_id' => $inscripcion->id,
                'grupo_id' => $inscripcion->grupo_id,
                'estudiante_id' => $inscripcion->estudiante_id,
                'nuevo_total' => $inscripcion->grupo->estudiantes_inscritos
            ]);

        } catch (\Exception $e) {
            Log::error('Error al incrementar contador de inscritos', [
                'inscripcion_id' => $inscripcion->id,
                'grupo_id' => $inscripcion->grupo_id,
                'error' => $e->getMessage()
            ]);

            // Re-lanzar la excepción para que la transacción haga rollback
            throw $e;
        }
    }

    /**
     * Evento: Antes de eliminar una inscripción
     *
     * Decrementa el contador estudiantes_inscritos del grupo
     *
     * @param inscripciones $inscripcion
     * @return void
     */
    public function deleting(inscripciones $inscripcion): void
    {
        try {
            // Obtener valor actual antes de decrementar
            $grupo = $inscripcion->grupo;
            $contadorActual = $grupo->estudiantes_inscritos;

            // Solo decrementar si el contador es mayor a 0 (evitar negativos)
            if ($contadorActual > 0) {
                $grupo->decrement('estudiantes_inscritos');

                Log::debug('Contador de inscritos decrementado', [
                    'inscripcion_id' => $inscripcion->id,
                    'grupo_id' => $inscripcion->grupo_id,
                    'estudiante_id' => $inscripcion->estudiante_id,
                    'contador_anterior' => $contadorActual,
                    'nuevo_total' => $contadorActual - 1
                ]);
            } else {
                Log::warning('Intento de decrementar contador ya en cero', [
                    'inscripcion_id' => $inscripcion->id,
                    'grupo_id' => $inscripcion->grupo_id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al decrementar contador de inscritos', [
                'inscripcion_id' => $inscripcion->id,
                'grupo_id' => $inscripcion->grupo_id,
                'error' => $e->getMessage()
            ]);

            // Re-lanzar la excepción para que la transacción haga rollback
            throw $e;
        }
    }

    /**
     * Evento: Después de restaurar una inscripción (soft delete)
     *
     * Incrementa nuevamente el contador
     *
     * @param inscripciones $inscripcion
     * @return void
     */
    public function restored(inscripciones $inscripcion): void
    {
        try {
            // Incrementar contador al restaurar
            $inscripcion->grupo()->increment('estudiantes_inscritos');

            Log::info('Inscripción restaurada, contador incrementado', [
                'inscripcion_id' => $inscripcion->id,
                'grupo_id' => $inscripcion->grupo_id,
                'estudiante_id' => $inscripcion->estudiante_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error al restaurar contador de inscritos', [
                'inscripcion_id' => $inscripcion->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
