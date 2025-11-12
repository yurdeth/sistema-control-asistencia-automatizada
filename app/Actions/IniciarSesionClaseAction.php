<?php

namespace App\Actions;

use App\Exceptions\Business\Auth\UnauthorizedException;
use App\Exceptions\Business\Sistema\SesionClaseException;
use App\Models\aulas;
use App\Models\horarios;
use App\Models\sesiones_clase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action para iniciar una sesión de clase
 *
 * Reemplaza el stored procedure: sp_iniciar_sesion_clase
 *
 * Funcionalidad:
 * 1. Valida que el usuario sea el docente asignado al horario
 * 2. Verifica que no exista sesión activa para hoy
 * 3. Crea o recupera la sesión de clase
 * 4. Actualiza estado del aula a 'ocupada'
 * 5. Registra hora de inicio real
 *
 * @package App\Actions
 * @version 1.0.0
 */
class IniciarSesionClaseAction
{
    /**
     * Ejecutar acción de iniciar sesión de clase
     *
     * @param int $horarioId ID del horario
     * @param int $usuarioId ID del usuario (docente)
     * @return array Datos de la sesión iniciada
     * @throws UnauthorizedException Si el usuario no es el docente asignado
     * @throws SesionClaseException Si la sesión ya fue iniciada
     */
    public function execute(int $horarioId, int $usuarioId): array
    {
        // 1. Buscar horario con relaciones necesarias
        $horario = horarios::with(['grupo.usuario', 'aula'])
            ->findOrFail($horarioId);

        // 2. Validar que el usuario sea el docente asignado
        if ($horario->grupo->usuario_id !== $usuarioId) {
            throw UnauthorizedException::notAssignedTeacher();
        }

        // 3. Verificar si ya existe una sesión para hoy
        $fechaHoy = now()->toDateString();

        $sesionExistente = sesiones_clase::where('horario_id', $horarioId)
            ->where('fecha_clase', $fechaHoy)
            ->first();

        // 4. Si existe y ya está en curso, lanzar excepción
        if ($sesionExistente && $sesionExistente->estado === 'en_curso') {
            throw SesionClaseException::yaIniciada(
                $sesionExistente->id,
                $sesionExistente->hora_inicio_real->format('H:i'),
                $horario->grupo->usuario->nombre_completo
            );
        }

        // 5. Iniciar sesión en transacción
        return DB::transaction(function () use ($horario, $fechaHoy, $sesionExistente) {
            // Crear o actualizar sesión
            if ($sesionExistente) {
                // Si existe pero no está en curso, reiniciarla
                $sesionExistente->update([
                    'estado' => 'en_curso',
                    'hora_inicio_real' => now(),
                    'hora_fin_real' => null,
                    'duracion_minutos' => null,
                    'retraso_minutos' => null
                ]);
                $sesion = $sesionExistente;
            } else {
                // Crear nueva sesión
                $sesion = sesiones_clase::create([
                    'horario_id' => $horario->id,
                    'fecha_clase' => $fechaHoy,
                    'hora_inicio_real' => now(),
                    'estado' => 'en_curso'
                ]);
            }

            // Actualizar estado del aula a 'ocupada'
            $horario->aula->update(['estado' => 'ocupada']);

            // Log de auditoría
            Log::info('Sesión de clase iniciada', [
                'sesion_id' => $sesion->id,
                'horario_id' => $horario->id,
                'aula_id' => $horario->aula_id,
                'docente_id' => $horario->grupo->usuario_id,
                'fecha' => $fechaHoy,
                'hora_inicio' => $sesion->hora_inicio_real->format('H:i:s')
            ]);

            return [
                'sesion_id' => $sesion->id,
                'horario_id' => $horario->id,
                'aula' => [
                    'id' => $horario->aula->id,
                    'nombre' => $horario->aula->nombre,
                    'codigo' => $horario->aula->codigo
                ],
                'grupo' => [
                    'id' => $horario->grupo->id,
                    'nombre' => $horario->grupo->nombre
                ],
                'materia' => [
                    'id' => $horario->grupo->materia->id,
                    'nombre' => $horario->grupo->materia->nombre
                ],
                'hora_inicio_programada' => $horario->hora_inicio,
                'hora_inicio_real' => $sesion->hora_inicio_real->format('H:i:s'),
                'estado' => $sesion->estado,
                'mensaje' => 'Sesión de clase iniciada correctamente'
            ];
        });
    }

    /**
     * Verificar si el docente puede iniciar clase en este horario
     *
     * @param int $horarioId
     * @param int $usuarioId
     * @return array ['puede_iniciar' => bool, 'razon' => string|null]
     */
    public function verificarPermiso(int $horarioId, int $usuarioId): array
    {
        try {
            $horario = horarios::with('grupo')->findOrFail($horarioId);

            if ($horario->grupo->usuario_id !== $usuarioId) {
                return [
                    'puede_iniciar' => false,
                    'razon' => 'No eres el docente asignado a este horario'
                ];
            }

            $sesionExistente = sesiones_clase::where('horario_id', $horarioId)
                ->where('fecha_clase', now()->toDateString())
                ->where('estado', 'en_curso')
                ->exists();

            if ($sesionExistente) {
                return [
                    'puede_iniciar' => false,
                    'razon' => 'La sesión para este horario ya fue iniciada hoy'
                ];
            }

            return [
                'puede_iniciar' => true,
                'razon' => null
            ];

        } catch (\Exception $e) {
            return [
                'puede_iniciar' => false,
                'razon' => 'Error al verificar permisos: ' . $e->getMessage()
            ];
        }
    }
}
