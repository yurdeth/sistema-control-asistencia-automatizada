<?php

namespace App\Exceptions\Business\Asistencia;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepción lanzada cuando el código QR escaneado es inválido o no corresponde
 *
 * Código HTTP: 400 Bad Request
 *
 * Casos:
 * - QR no existe en la base de datos
 * - QR expirado
 * - QR corresponde a otra aula
 *
 * @package App\Exceptions\Business\Asistencia
 */
class QRInvalidoException extends BusinessException
{
    protected string $errorCode = ErrorCode::INVALID_QR->value;
    protected int $httpStatus = 400;

    /**
     * QR no reconocido
     *
     * @param string $codigoQR
     * @return static
     */
    public static function noReconocido(string $codigoQR): static
    {
        return new static(
            message: 'El código QR escaneado no es válido o no existe en el sistema',
            context: [
                'codigo_qr' => substr($codigoQR, 0, 20) . '...' // Solo primeros 20 caracteres por seguridad
            ]
        );
    }

    /**
     * QR expirado
     *
     * @param string $codigoQR
     * @param string $fechaExpiracion
     * @return static
     */
    public static function expirado(string $codigoQR, string $fechaExpiracion): static
    {
        return new static(
            message: 'Este código QR expiró el ' . $fechaExpiracion,
            errorCode: ErrorCode::EXPIRED_QR->value,
            context: [
                'codigo_qr' => substr($codigoQR, 0, 20) . '...',
                'fecha_expiracion' => $fechaExpiracion
            ]
        );
    }

    /**
     * QR no coincide con el aula de la sesión
     *
     * @param int $aulaQR ID del aula del QR escaneado
     * @param string $nombreAulaQR
     * @param int $aulaSesion ID del aula donde debería estar la clase
     * @param string $nombreAulaSesion
     * @return static
     */
    public static function aulaNoCoincide(
        int $aulaQR,
        string $nombreAulaQR,
        int $aulaSesion,
        string $nombreAulaSesion
    ): static {
        return new static(
            message: "El QR corresponde al aula '{$nombreAulaQR}', pero la clase es en el aula '{$nombreAulaSesion}'",
            errorCode: ErrorCode::QR_CLASSROOM_MISMATCH->value,
            httpStatus: 422,
            context: [
                'aula_qr' => $aulaQR,
                'aula_qr_nombre' => $nombreAulaQR,
                'aula_sesion' => $aulaSesion,
                'aula_sesion_nombre' => $nombreAulaSesion
            ]
        );
    }
}
