<?php

namespace App\Exceptions\Business\Horario;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepción lanzada cuando se detecta un conflicto de horarios
 *
 * Código HTTP: 409 Conflict
 *
 * Reemplaza el trigger SQL: trg_validar_conflicto_horario
 *
 * Casos:
 * - Aula ocupada en el mismo horario
 * - Docente con otra clase asignada
 * - Estudiante con clases simultáneas
 *
 * @package App\Exceptions\Business\Horario
 */
class ConflictoHorarioException extends BusinessException
{
    protected string $errorCode = ErrorCode::SCHEDULE_CONFLICT->value;
    protected int $httpStatus = 409;

    /**
     * Conflicto en el aula
     *
     * @param int $aulaId
     * @param string $aulaNombre
     * @param string $diaSemana
     * @param string $horaInicio
     * @param string $horaFin
     * @param string|null $grupoConflicto Grupo que ya ocupa el aula
     * @return static
     */
    public static function enAula(
        int $aulaId,
        string $aulaNombre,
        string $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?string $grupoConflicto = null
    ): static {
        $mensaje = "El aula '{$aulaNombre}' ya está ocupada el {$diaSemana} de {$horaInicio} a {$horaFin}";

        if ($grupoConflicto) {
            $mensaje .= " (grupo: {$grupoConflicto})";
        }

        return new static(
            message: $mensaje,
            context: [
                'aula_id' => $aulaId,
                'aula_nombre' => $aulaNombre,
                'dia_semana' => $diaSemana,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'grupo_conflicto' => $grupoConflicto
            ]
        );
    }

    /**
     * Conflicto para el docente
     *
     * @param int $docenteId
     * @param string $docenteNombre
     * @param string $diaSemana
     * @param string $horaInicio
     * @param string $horaFin
     * @param string|null $materiaConflicto Materia que ya tiene asignada
     * @return static
     */
    public static function paraDocente(
        int $docenteId,
        string $docenteNombre,
        string $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?string $materiaConflicto = null
    ): static {
        $mensaje = "{$docenteNombre} ya tiene otra clase asignada el {$diaSemana} de {$horaInicio} a {$horaFin}";

        if ($materiaConflicto) {
            $mensaje .= " (materia: {$materiaConflicto})";
        }

        return new static(
            message: $mensaje,
            errorCode: ErrorCode::TEACHER_SCHEDULE_CONFLICT->value,
            context: [
                'docente_id' => $docenteId,
                'dia_semana' => $diaSemana,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'materia_conflicto' => $materiaConflicto
            ]
        );
    }

    /**
     * Conflicto para el estudiante
     *
     * @param int $estudianteId
     * @param string $estudianteNombre
     * @param string $diaSemana
     * @param string $horaInicio
     * @param array $materiasConflicto Lista de materias en conflicto
     * @return static
     */
    public static function paraEstudiante(
        int $estudianteId,
        string $estudianteNombre,
        string $diaSemana,
        string $horaInicio,
        array $materiasConflicto
    ): static {
        $materias = implode(', ', $materiasConflicto);

        return new static(
            message: "{$estudianteNombre} ya tiene clases el {$diaSemana} a las {$horaInicio} ({$materias})",
            errorCode: ErrorCode::STUDENT_SCHEDULE_CONFLICT->value,
            context: [
                'estudiante_id' => $estudianteId,
                'dia_semana' => $diaSemana,
                'hora_inicio' => $horaInicio,
                'materias_conflicto' => $materiasConflicto
            ]
        );
    }
}
