<?php

namespace App\Exceptions\Business\Inscripcion;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepciones relacionadas con solicitudes de inscripción
 *
 * @package App\Exceptions\Business\Inscripcion
 */
class SolicitudInscripcionException extends BusinessException
{
    protected string $errorCode = ErrorCode::ENROLLMENT_REQUEST_NOT_FOUND->value;
    protected int $httpStatus = 404;

    /**
     * Solicitud no encontrada
     *
     * @param int $solicitudId
     * @return static
     */
    public static function notFound(int $solicitudId): static
    {
        return new static(
            message: 'Solicitud de inscripción no encontrada',
            context: ['solicitud_id' => $solicitudId]
        );
    }

    /**
     * Solicitud ya procesada (aceptada o rechazada)
     *
     * @param int $solicitudId
     * @param string $estadoActual
     * @return static
     */
    public static function alreadyProcessed(int $solicitudId, string $estadoActual): static
    {
        return new static(
            message: "Esta solicitud ya fue {$estadoActual}",
            errorCode: ErrorCode::ENROLLMENT_REQUEST_ALREADY_PROCESSED->value,
            httpStatus: 409,
            context: [
                'solicitud_id' => $solicitudId,
                'estado' => $estadoActual
            ]
        );
    }

    /**
     * Solicitud expirada por tiempo
     *
     * @param int $solicitudId
     * @param string $fechaSolicitud
     * @param int $diasExpiracion
     * @return static
     */
    public static function expired(int $solicitudId, string $fechaSolicitud, int $diasExpiracion): static
    {
        return new static(
            message: "Esta solicitud expiró después de {$diasExpiracion} días sin respuesta",
            errorCode: ErrorCode::ENROLLMENT_REQUEST_EXPIRED->value,
            httpStatus: 422,
            context: [
                'solicitud_id' => $solicitudId,
                'fecha_solicitud' => $fechaSolicitud,
                'dias_expiracion' => $diasExpiracion
            ]
        );
    }
}
