<?php

namespace App\Exceptions\Business\Asistencia;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepción lanzada cuando se intenta registrar asistencia pero no hay sesión activa
 *
 * Código HTTP: 404 Not Found
 *
 * Casos:
 * - Estudiante escanea QR fuera del horario de clase
 * - Docente no ha iniciado la sesión
 * - Sesión ya fue finalizada
 *
 * Reemplaza validación en: sp_registrar_asistencia_estudiante
 *
 * @package App\Exceptions\Business\Asistencia
 */
class SesionNoActivaException extends BusinessException
{
    protected string $errorCode = ErrorCode::NO_ACTIVE_SESSION->value;
    protected int $httpStatus = 404;

    /**
     * No hay sesión activa en esta aula
     *
     * @param int $aulaId
     * @param string $aulaNombre
     * @return static
     */
    public static function enAula(int $aulaId, string $aulaNombre): static
    {
        return new static(
            message: "No hay una clase en curso en el aula '{$aulaNombre}' en este momento",
            context: [
                'aula_id' => $aulaId,
                'aula_nombre' => $aulaNombre,
                'fecha_actual' => now()->toDateString(),
                'hora_actual' => now()->toTimeString()
            ]
        );
    }

    /**
     * Sesión existe pero no está en estado "en_curso"
     *
     * @param int $sesionId
     * @param string $estadoActual
     * @return static
     */
    public static function estadoInvalido(int $sesionId, string $estadoActual): static
    {
        return new static(
            message: "La sesión de clase está en estado '{$estadoActual}' (debe estar 'en curso')",
            errorCode: ErrorCode::SESSION_NOT_ACTIVE->value,
            context: [
                'sesion_id' => $sesionId,
                'estado_actual' => $estadoActual
            ]
        );
    }

    /**
     * Sesión ya finalizó
     *
     * @param int $sesionId
     * @param string $horaFin
     * @return static
     */
    public static function yaFinalizada(int $sesionId, string $horaFin): static
    {
        return new static(
            message: "Esta clase ya finalizó a las {$horaFin}",
            errorCode: ErrorCode::SESSION_ALREADY_ENDED->value,
            context: [
                'sesion_id' => $sesionId,
                'hora_fin' => $horaFin
            ]
        );
    }
}
