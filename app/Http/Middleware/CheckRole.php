<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware escalable para verificar permisos por rol
 *
 * Uso en rutas:
 * Route::get('/ruta', ...)->middleware('role:1,2,6'); // Permite roles 1, 2 y 6
 * Route::get('/ruta', ...)->middleware('role:root'); // Solo ROOT
 *
 * El rol 1 (ROOT) siempre tiene acceso a todo
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  IDs de roles permitidos o nombres de roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Verificar que el usuario esté autenticado
        $user = Auth::guard('api')->user();

        if (!$user) {
            return redirect('/login');
        }

        $userRoleId = DB::table('usuario_roles')
            ->where('usuario_id', $user->id)
            ->value('rol_id');

        if ($userRoleId == 1) {
            return $next($request);
        }

        // Verificar si el rol del usuario está en los roles permitidos
        foreach ($roles as $role) {
            // Permitir comparación por ID numérico
            if (is_numeric($role) && $userRoleId == intval($role)) {
                return $next($request);
            }

            //comparación por nombre de rol (opcional)
            if (!is_numeric($role)) {
                $roleId = DB::table('roles')
                    ->where('nombre', $role)
                    ->value('id');

                if ($userRoleId == $roleId) {
                    return $next($request);
                }
            }
        }

        // Si no tiene permiso, redirigir al dashboard con mensaje de error
        return redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta página');
    }
}
