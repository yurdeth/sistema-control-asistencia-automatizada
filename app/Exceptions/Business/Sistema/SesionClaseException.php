<?php

namespace App\Exceptions\Business\Sistema;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepciones relacionadas con sesiones de clase
 *
 * Reemplaza validaciones en: sp_iniciar_sesion_clase y sp_finalizar_sesion_clase
 *
 * @package App\Exceptions\Business\Sistema
 */
class SesionClaseException extends BusinessException
{
    protected string $errorCode = ErrorCode::SESSION_ALREADY_STARTED->value;
    protected int $httpStatus = 409;

    /**
     * Sesión ya fue iniciada
     *
     * @param int $sesionId
     * @param string $horaInicio
     * @param string $docenteNombre
     * @return static
     */
    public static function yaIniciada(int $sesionId, string $horaInicio, string $docenteNombre): static
    {
        return new static(
            message: "Esta clase ya fue iniciada a las {$horaInicio} por {$docenteNombre}",
            context: [
                'sesion_id' => $sesionId,
                'hora_inicio' => $horaInicio
            ]
        );
    }

    /**
     * Sesión ya fue finalizada
     *
     * @param int $sesionId
     * @param string $horaFin
     * @return static
     */
    public static function yaFinalizada(int $sesionId, string $horaFin): static
    {
        return new static(
            message: "Esta clase ya fue finalizada a las {$horaFin}",
            errorCode: ErrorCode::SESSION_ALREADY_ENDED->value,
            context: [
                'sesion_id' => $sesionId,
                'hora_fin' => $horaFin
            ]
        );
    }

    /**
     * No se puede finalizar sesión que no está activa
     *
     * @param int $sesionId
     * @param string $estadoActual
     * @return static
     */
    public static function noSePuedeFinalizar(int $sesionId, string $estadoActual): static
    {
        return new static(
            message: "No se puede finalizar una clase que está en estado '{$estadoActual}'",
            errorCode: ErrorCode::CANNOT_END_INACTIVE_SESSION->value,
            httpStatus: 422,
            context: [
                'sesion_id' => $sesionId,
                'estado_actual' => $estadoActual
            ]
        );
    }

    /**
     * Sesión no pertenece al horario indicado
     *
     * @param int $sesionId
     * @param int $horarioEsperado
     * @param int $horarioReal
     * @return static
     */
    public static function horarioNoCoincide(int $sesionId, int $horarioEsperado, int $horarioReal): static
    {
        return new static(
            message: 'Esta sesión no corresponde al horario seleccionado',
            errorCode: ErrorCode::SESSION_HORARIO_MISMATCH->value,
            httpStatus: 422,
            context: [
                'sesion_id' => $sesionId,
                'horario_esperado' => $horarioEsperado,
                'horario_real' => $horarioReal
            ]
        );
    }
}
