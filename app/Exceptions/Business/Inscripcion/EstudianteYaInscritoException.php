<?php

namespace App\Exceptions\Business\Inscripcion;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepción lanzada cuando un estudiante intenta inscribirse en un grupo/materia donde ya está inscrito
 *
 * Código HTTP: 409 Conflict
 *
 * @package App\Exceptions\Business\Inscripcion
 */
class EstudianteYaInscritoException extends BusinessException
{
    protected string $errorCode = ErrorCode::ALREADY_ENROLLED->value;
    protected int $httpStatus = 409;

    /**
     * Estudiante ya inscrito en este grupo específico
     *
     * @param int $estudianteId
     * @param string $estudianteNombre
     * @param int $grupoId
     * @param string $grupoNombre
     * @return static
     */
    public static function enGrupo(int $estudianteId, string $estudianteNombre, int $grupoId, string $grupoNombre): static
    {
        return new static(
            message: "{$estudianteNombre} ya está inscrito en el grupo '{$grupoNombre}'",
            context: [
                'estudiante_id' => $estudianteId,
                'grupo_id' => $grupoId
            ]
        );
    }

    /**
     * Estudiante ya inscrito en algún grupo de esta materia
     *
     * @param int $estudianteId
     * @param string $estudianteNombre
     * @param int $materiaId
     * @param string $materiaNombre
     * @param string $grupoActual Nombre del grupo donde ya está inscrito
     * @return static
     */
    public static function enMateria(
        int $estudianteId,
        string $estudianteNombre,
        int $materiaId,
        string $materiaNombre,
        string $grupoActual
    ): static {
        return new static(
            message: "{$estudianteNombre} ya está inscrito en la materia '{$materiaNombre}' (grupo: {$grupoActual})",
            errorCode: ErrorCode::ALREADY_ENROLLED_IN_SUBJECT->value,
            context: [
                'estudiante_id' => $estudianteId,
                'materia_id' => $materiaId,
                'grupo_actual' => $grupoActual
            ]
        );
    }
}
