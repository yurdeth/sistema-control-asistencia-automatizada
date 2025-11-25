<?php

namespace App\Http\Concerns;

use App\RolesEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ValidatesRoles
{
    /**
     * Verifica si el usuario actual tiene uno de los roles permitidos
     *
     * @param array $rolesPermitidos Array de RolesEnum valores o IDs numéricos
     * @param string|null $mensajeErrorPersonalizado Mensaje de error personalizado
     * @return JsonResponse|null Retorna JsonResponse si no tiene permiso, null si tiene permiso
     */
    protected function validarPermisos(array $rolesPermitidos, ?string $mensajeErrorPersonalizado = null): ?JsonResponse
    {
        // Verificar autenticación
        if (!Auth::check()) {
            Log::warning('Acceso no autorizado - usuario no autenticado', [
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
                'method' => request()->method()
            ]);

            return response()->json([
                'message' => $mensajeErrorPersonalizado ?? 'Acceso no autorizado',
                'success' => false,
                'error' => 'UNAUTHORIZED'
            ], 401);
        }

        // El rol ROOT (ID 1) tiene acceso a todo
        $userRoleId = $this->getUserRoleId();
        if ($userRoleId === 1) {
            return null; // ROOT tiene acceso a todo
        }

        // Convertir roles permitidos a IDs numéricos para comparación
        $rolesIdsPermitidos = [];
        foreach ($rolesPermitidos as $rol) {
            if (is_numeric($rol)) {
                $rolesIdsPermitidos[] = intval($rol);
            } elseif ($rol instanceof RolesEnum) {
                $rolesIdsPermitidos[] = $rol->value;
            }
        }

        // Verificar si el rol del usuario está en los permitidos
        if (!in_array($userRoleId, $rolesIdsPermitidos)) {
            Log::warning('Acceso denegado - rol no autorizado', [
                'user_id' => Auth::id(),
                'user_role_id' => $userRoleId,
                'required_roles' => $rolesIdsPermitidos,
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
                'method' => request()->method()
            ]);

            return response()->json([
                'message' => $mensajeErrorPersonalizado ?? 'No tienes permisos para realizar esta acción',
                'success' => false,
                'error' => 'FORBIDDEN'
            ], 403);
        }

        return null; // Tiene permiso
    }

    /**
     * Obtiene el ID del rol del usuario actual
     */
    protected function getUserRoleId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        return DB::table('usuario_roles')
            ->where('usuario_id', Auth::id())
            ->value('rol_id');
    }

    /**
     * Obtiene el nombre del rol del usuario actual como RolesEnum
     */
    protected function getUserRoleName(): ?RolesEnum
    {
        $roleId = $this->getUserRoleId();
        if (!$roleId) {
            return null;
        }

        return RolesEnum::tryFrom($roleId);
    }

    /**
     * Verifica permisos para operaciones de creación/edición de entidades académicas
     */
    protected function validarPermisosAdministracion(?string $entidad = null): ?JsonResponse
    {
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
        ];

        $mensaje = $entidad
            ? "No tienes permisos para gestionar {$entidad}"
            : "No tienes permisos de administración";

        return $this->validarPermisos($rolesPermitidos, $mensaje);
    }

    /**
     * Verifica permisos para gestión académica básica
     */
    protected function validarPermisosGestionAcademica(?string $entidad = null): ?JsonResponse
    {
        $rolesPermitidos = [
            RolesEnum::ROOT->value,
            RolesEnum::ADMINISTRADOR_ACADEMICO->value,
            RolesEnum::JEFE_DEPARTAMENTO->value,
            RolesEnum::COORDINADOR_CARRERAS->value,
        ];

        $mensaje = $entidad
            ? "No tienes permisos para gestionar {$entidad}"
            : "No tienes permisos de gestión académica";

        return $this->validarPermisos($rolesPermitidos, $mensaje);
    }

    /**
     * Verifica permisos exclusivos para docentes
     */
    protected function validarPermisosDocente(?string $accion = null): ?JsonResponse
    {
        $rolesPermitidos = [
            RolesEnum::DOCENTE->value,
        ];

        $mensaje = $accion
            ? "Acción '{$accion}' es exclusiva para docentes"
            : "Esta función es exclusiva para docentes";

        return $this->validarPermisos($rolesPermitidos, $mensaje);
    }

    /**
     * Verifica permisos para estudiantes (solo lectura)
     */
    protected function validarPermisosEstudiante(?string $recurso = null): ?JsonResponse
    {
        $rolesPermitidos = [
            RolesEnum::ESTUDIANTE->value,
        ];

        $mensaje = $recurso
            ? "No tienes permisos para consultar {$recurso}"
            : "No tienes permisos de estudiante";

        return $this->validarPermisos($rolesPermitidos, $mensaje);
    }
}