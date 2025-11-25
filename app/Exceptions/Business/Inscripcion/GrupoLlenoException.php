<?php

namespace App\Exceptions\Business\Inscripcion;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepción lanzada cuando se intenta inscribir a un estudiante en un grupo que ya alcanzó su capacidad máxima
 *
 * Código HTTP: 422 Unprocessable Entity
 *
 * Reemplaza el trigger SQL: trg_validar_capacidad_grupo
 *
 * @package App\Exceptions\Business\Inscripcion
 */
class GrupoLlenoException extends BusinessException
{
    protected string $errorCode = ErrorCode::GROUP_FULL->value;
    protected int $httpStatus = 422;

    /**
     * Crear excepción con datos del grupo
     *
     * @param int $grupoId ID del grupo
     * @param string $grupoNombre Nombre del grupo
     * @param int $capacidad Capacidad máxima del grupo
     * @param int $inscritos Estudiantes ya inscritos
     * @return static
     */
    public static function create(int $grupoId, string $grupoNombre, int $capacidad, int $inscritos): static
    {
        return new static(
            message: "El grupo '{$grupoNombre}' ha alcanzado su capacidad máxima de {$capacidad} estudiantes ({$inscritos}/{$capacidad} inscritos)",
            context: [
                'grupo_id' => $grupoId,
                'grupo_nombre' => $grupoNombre,
                'capacidad_maxima' => $capacidad,
                'estudiantes_inscritos' => $inscritos,
                'cupos_disponibles' => max(0, $capacidad - $inscritos)
            ]
        );
    }

    /**
     * Crear desde modelo Grupo
     *
     * @param \App\Models\Grupo $grupo
     * @return static
     */
    public static function fromGrupo($grupo): static
    {
        return self::create(
            $grupo->id,
            $grupo->nombre,
            $grupo->capacidad_maxima,
            $grupo->estudiantes_inscritos
        );
    }
}
