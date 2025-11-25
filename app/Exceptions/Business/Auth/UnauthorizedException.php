<?php

namespace App\Exceptions\Business\Auth;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * Excepción lanzada cuando un usuario no tiene permiso para realizar una acción
 *
 * Código HTTP: 403 Forbidden
 *
 * Casos de uso:
 * - Usuario intenta iniciar clase sin ser el docente asignado
 * - Gestor intenta modificar recurso de otro departamento
 * - Rol insuficiente para la operación
 *
 * @package App\Exceptions\Business\Auth
 */
class UnauthorizedException extends BusinessException
{
    protected string $errorCode = ErrorCode::UNAUTHORIZED->value;
    protected int $httpStatus = 403;

    /**
     * Usuario no es el docente asignado
     *
     * @return static
     */
    public static function notAssignedTeacher(): static
    {
        return new static(
            message: 'No tienes permiso para iniciar esta clase. Solo el docente asignado puede hacerlo.',
            errorCode: ErrorCode::NOT_ASSIGNED_TEACHER->value
        );
    }

    /**
     * Usuario no es gestor del recurso
     *
     * @param string $recurso Nombre del recurso (departamento, carrera, etc.)
     * @return static
     */
    public static function notManager(string $recurso): static
    {
        return new static(
            message: "No tienes permiso para gestionar {$recurso}",
            errorCode: ErrorCode::NOT_MANAGER->value
        );
    }

    /**
     * Rol insuficiente para la operación
     *
     * @param array $rolesRequeridos Lista de roles necesarios
     * @return static
     */
    public static function insufficientRole(array $rolesRequeridos): static
    {
        $roles = implode(', ', $rolesRequeridos);

        return new static(
            message: 'Tu rol no tiene permisos para esta acción',
            errorCode: ErrorCode::INSUFFICIENT_ROLE->value,
            context: [
                'required_roles' => $rolesRequeridos,
                'user_role' => auth()->check() ? auth()->user()->roles->pluck('nombre')->toArray() : null
            ]
        );
    }

    /**
     * Acción genérica no autorizada
     *
     * @param string $accion Descripción de la acción
     * @return static
     */
    public static function forAction(string $accion): static
    {
        return new static("No tienes permiso para {$accion}");
    }
}
