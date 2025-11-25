<?php

namespace App\Exceptions\Business\Asistencia;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepción lanzada cuando un estudiante intenta registrar asistencia sin estar inscrito en el grupo
 *
 * Código HTTP: 403 Forbidden
 *
 * Reemplaza validación en: sp_registrar_asistencia_estudiante
 *
 * @package App\Exceptions\Business\Asistencia
 */
class EstudianteNoInscritoException extends BusinessException
{
    protected string $errorCode = ErrorCode::STUDENT_NOT_ENROLLED->value;
    protected int $httpStatus = 403;

    /**
     * Estudiante no inscrito en el grupo
     *
     * @param int $estudianteId
     * @param string $estudianteNombre
     * @param int $grupoId
     * @param string $grupoNombre
     * @param string $materiaNombre
     * @return static
     */
    public static function enGrupo(
        int $estudianteId,
        string $estudianteNombre,
        int $grupoId,
        string $grupoNombre,
        string $materiaNombre
    ): static {
        return new static(
            message: "No estás inscrito en el grupo '{$grupoNombre}' de {$materiaNombre}. Solicita tu inscripción primero.",
            context: [
                'estudiante_id' => $estudianteId,
                'estudiante_nombre' => $estudianteNombre,
                'grupo_id' => $grupoId,
                'grupo_nombre' => $grupoNombre,
                'materia_nombre' => $materiaNombre
            ]
        );
    }

    /**
     * Estudiante con inscripción pendiente
     *
     * @param int $estudianteId
     * @param int $grupoId
     * @param string $estadoSolicitud
     * @return static
     */
    public static function solicitudPendiente(int $estudianteId, int $grupoId, string $estadoSolicitud): static
    {
        $mensaje = match($estadoSolicitud) {
            'pendiente' => 'Tu solicitud de inscripción está pendiente de aprobación',
            'rechazada' => 'Tu solicitud de inscripción fue rechazada',
            default => 'Tu inscripción aún no está activa'
        };

        return new static(
            message: $mensaje,
            context: [
                'estudiante_id' => $estudianteId,
                'grupo_id' => $grupoId,
                'estado_solicitud' => $estadoSolicitud
            ]
        );
    }
}
