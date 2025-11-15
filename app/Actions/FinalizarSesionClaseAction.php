<?php

namespace App\Actions;

use App\Exceptions\Business\Auth\UnauthorizedException;
use App\Exceptions\Business\Sistema\SesionClaseException;
use App\Models\horarios;
use App\Models\sesiones_clase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action para finalizar una sesión de clase
 *
 * Reemplaza el stored procedure: sp_finalizar_sesion_clase
 *
 * Funcionalidad:
 * 1. Valida que el usuario sea el docente asignado
 * 2. Verifica que la sesión esté en estado 'en_curso'
 * 3. Registra hora de fin real
 * 4. Calcula duración y retraso automáticamente (vía Observer)
 * 5. Actualiza estado del aula a 'disponible' si no hay otras sesiones activas
 *
 * @package App\Actions
 * @version 1.0.0
 */
class FinalizarSesionClaseAction
{
    /**
     * Ejecutar acción de finalizar sesión de clase
     *
     * @param int $sesionId ID de la sesión a finalizar
     * @param int $usuarioId ID del usuario (docente)
     * @return array Datos de la sesión finalizada
     * @throws UnauthorizedException Si el usuario no es el docente asignado
     * @throws SesionClaseException Si la sesión no está activa o ya fue finalizada
     */
    public function execute(int $sesionId, int $usuarioId): array
    {
        // 1. Buscar sesión con relaciones necesarias
        $sesion = sesiones_clase::with(['horario.grupo.docente', 'horario.aula'])
            ->findOrFail($sesionId);

        // 2. Validar que el usuario sea el docente asignado
        if ($sesion->horario->grupo->docente_id !== $usuarioId) {
            throw UnauthorizedException::notAssignedTeacher();
        }

        // 3. Validar estado de la sesión
        if ($sesion->estado === 'finalizada') {
            throw SesionClaseException::yaFinalizada(
                $sesion->id,
                $sesion->hora_fin_real->format('H:i')
            );
        }

        if ($sesion->estado !== 'en_curso') {
            throw SesionClaseException::noSePuedeFinalizar(
                $sesion->id,
                $sesion->estado
            );
        }

        // 4. Finalizar sesión en transacción
        return DB::transaction(function () use ($sesion) {
            // Actualizar sesión
            $sesion->update([
                'estado' => 'finalizada',
                'hora_fin_real' => now()
            ]);

            // Nota: duracion_minutos y retraso_minutos se calculan automáticamente
            // en el SesionClaseObserver al actualizar hora_fin_real

            // Verificar si hay otras sesiones activas en la misma aula
            $otrasSesionesActivas = sesiones_clase::where('id', '!=', $sesion->id)
                ->whereHas('horario', function ($q) use ($sesion) {
                    $q->where('aula_id', $sesion->horario->aula_id);
                })
                ->where('estado', 'en_curso')
                ->whereDate('fecha_clase', now()->toDateString())
                ->exists();

            // Solo liberar el aula si no hay otras sesiones activas
            if (!$otrasSesionesActivas) {
                $sesion->horario->aula->update(['estado' => 'disponible']);
            }

            // Refrescar modelo para obtener cálculos del Observer
            $sesion->refresh();

            // Calcular estadísticas de asistencia
            $totalAsistencias = $sesion->asistencias()->count();
            $totalInscritos = $sesion->horario->grupo->estudiantes_inscrito ?? 0;
            $porcentajeAsistencia = $totalInscritos > 0
                ? round(($totalAsistencias / $totalInscritos) * 100, 2)
                : 0;

            // Log de auditoría
            Log::info('Sesión de clase finalizada', [
                'sesion_id' => $sesion->id,
                'horario_id' => $sesion->horario_id,
                'aula_id' => $sesion->horario->aula_id,
                'docente_id' => $sesion->horario->grupo->docente_id,
                'fecha' => $sesion->fecha_clase,
                'hora_fin' => $sesion->hora_fin_real->format('H:i:s'),
                'duracion_minutos' => $sesion->duracion_minutos,
                'retraso_minutos' => $sesion->retraso_minutos,
                'total_asistencias' => $totalAsistencias,
                'porcentaje_asistencia' => $porcentajeAsistencia
            ]);

            return [
                'sesion_id' => $sesion->id,
                'horario_id' => $sesion->horario_id,
                'aula' => [
                    'id' => $sesion->horario->aula->id,
                    'nombre' => $sesion->horario->aula->nombre,
                    'estado' => $sesion->horario->aula->estado
                ],
                'grupo' => [
                    'id' => $sesion->horario->grupo->id,
                ],
                'materia' => [
                    'id' => $sesion->horario->grupo->materia->id,
                    'nombre' => $sesion->horario->grupo->materia->nombre
                ],
                'hora_inicio_programada' => $sesion->horario->hora_inicio,
                'hora_inicio_real' => $sesion->hora_inicio_real->format('H:i:s'),
                'hora_fin_programada' => $sesion->horario->hora_fin,
                'hora_fin_real' => $sesion->hora_fin_real->format('H:i:s'),
                'duracion_minutos' => $sesion->duracion_minutos,
                'retraso_minutos' => $sesion->retraso_minutos,
                'estadisticas' => [
                    'total_asistencias' => $totalAsistencias,
                    'total_inscritos' => $totalInscritos,
                    'porcentaje_asistencia' => $porcentajeAsistencia
                ],
                'estado' => $sesion->estado,
                'mensaje' => 'Sesión de clase finalizada correctamente'
            ];
        });
    }

    /**
     * Finalizar sesión por ID de horario (alternativa)
     *
     * Útil cuando solo se tiene el horario_id y la fecha
     *
     * @param int $horarioId
     * @param int $usuarioId
     * @param string|null $fecha Fecha de la clase (default: hoy)
     * @return array
     */
    public function finalizarPorHorario(int $horarioId, int $usuarioId, ?string $fecha = null): array
    {
        $fecha = $fecha ?? now()->toDateString();

        $sesion = sesiones_clase::where('horario_id', $horarioId)
            ->where('fecha_clase', $fecha)
            ->where('estado', 'en_curso')
            ->firstOrFail();

        return $this->execute($sesion->id, $usuarioId);
    }
}
