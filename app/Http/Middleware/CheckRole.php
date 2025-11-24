<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware escalable para verificar permisos por rol con optimización y logging
 *
 * Uso en rutas:
 * Route::get('/ruta', ...)->middleware('role:1,2,6'); // Permite roles 1, 2 y 6
 * Route::get('/ruta', ...)->middleware('role:root'); // Solo ROOT
 *
 * El rol 1 (ROOT) siempre tiene acceso a todo
 *
 * Características:
 * - Caching de roles para optimización
 * - Logging de accesos y denegaciones
 * - Validación robusta con mensajes claros
 * - Soporte para IDs numéricos y nombres de rol
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
        try {
            // Verificar que el usuario esté autenticado
            $user = Auth::guard('api')->user();

            if (!$user) {
                Log::warning('Acceso denegado - usuario no autenticado', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->header('referer')
                ]);

                return redirect('/login');
            }

            // Obtener rol del usuario con caching
            $userRoleId = $this->getUserRoleCached($user->id);

            // El rol ROOT (ID 1) tiene acceso a todo
            if ($userRoleId === 1) {
                Log::info('Acceso concedido - usuario ROOT', [
                    'user_id' => $user->id,
                    'url' => $request->fullUrl(),
                    'method' => $request->method()
                ]);

                return $next($request);
            }

            // Convertir roles permitidos a IDs numéricos con caching
            $allowedRoleIds = $this->getAllowedRoleIdsCached($roles);

            // Verificar si el rol del usuario está en los permitidos
            if (in_array($userRoleId, $allowedRoleIds)) {
                Log::info('Acceso concedido - rol autorizado', [
                    'user_id' => $user->id,
                    'user_role_id' => $userRoleId,
                    'allowed_roles' => $allowedRoleIds,
                    'url' => $request->fullUrl(),
                    'method' => $request->method()
                ]);

                return $next($request);
            }

            // Log de acceso denegado
            Log::warning('Acceso denegado - rol no autorizado', [
                'user_id' => $user->id,
                'user_role_id' => $userRoleId,
                'allowed_roles' => $allowedRoleIds,
                'requested_roles' => $roles,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer')
            ]);

            // Determinar el mejor destino según el rol del usuario
            $redirectPath = $this->getRedirectPathForRole($userRoleId);

            return redirect($redirectPath)->with('error', 'No tienes permisos para acceder a esta página');

        } catch (\Exception $e) {
            Log::error('Error en middleware CheckRole', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'trace' => $e->getTraceAsString()
            ]);

            // En caso de error, permitir acceso o redirigir según configuración
            if (config('app.debug')) {
                throw $e;
            }

            return redirect('/dashboard')->with('error', 'Error de verificación de permisos');
        }
    }

    /**
     * Obtiene el rol del usuario con caching
     */
    private function getUserRoleCached(int $userId): ?int
    {
        return Cache::remember(
            "user_role_{$userId}",
            3600, // 1 hora
            function () use ($userId) {
                return DB::table('usuario_roles')
                    ->where('usuario_id', $userId)
                    ->value('rol_id');
            }
        );
    }

    /**
     * Convierte roles permitidos a IDs numéricos con caching
     */
    private function getAllowedRoleIdsCached(array $roles): array
    {
        $cacheKey = 'allowed_roles_' . md5(implode(',', $roles));

        return Cache::remember(
            $cacheKey,
            1800, // 30 minutos
            function () use ($roles) {
                $allowedIds = [];

                foreach ($roles as $role) {
                    if (is_numeric($role)) {
                        // Es un ID numérico directamente
                        $allowedIds[] = intval($role);
                    } else {
                        // Es un nombre de rol, buscar el ID
                        $roleId = DB::table('roles')
                            ->where('nombre', $role)
                            ->value('id');

                        if ($roleId) {
                            $allowedIds[] = $roleId;
                        }
                    }
                }

                return array_unique($allowedIds);
            }
        );
    }

    /**
     * Determina la mejor ruta de redirección según el rol del usuario
     */
    private function getRedirectPathForRole(?int $userRoleId): string
    {
        if (!$userRoleId) {
            return '/login';
        }

        // Mapeo de roles a rutas preferenciales
        $roleRedirects = [
            1 => '/dashboard', // ROOT
            2 => '/dashboard', // Administrador Académico
            3 => '/dashboard/disponibilidad', // Gestor Departamento
            4 => '/dashboard/disponibilidad', // Gestor Carrera
            5 => '/dashboard/consultar-disponibilidad', // Profesor
            6 => '/dashboard/catalogo', // Estudiante
            7 => '/dashboard/catalogo', // Invitado (no tiene lógica pero es mejor que quede definido)
        ];

        return $roleRedirects[$userRoleId] ?? '/dashboard';
    }

    /**
     * Limpia el cache de roles cuando un usuario cambia de rol
     */
    public static function clearUserRoleCache(int $userId): void
    {
        Cache::forget("user_role_{$userId}");

        // También limpiamos caché de roles permitidos si es necesario
        $keys = Cache::get('allowed_roles_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Limpia todo el caché de roles (útil para cambios masivos)
     */
    public static function clearAllRoleCache(): void
    {
        // Limpiar cachés de roles de usuario
        $userIds = DB::table('usuario_roles')->pluck('usuario_id');
        foreach ($userIds as $userId) {
            Cache::forget("user_role_{$userId}");
        }

        // Limpiar cachés de roles permitidos
        $keys = Cache::get('allowed_roles_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget('allowed_roles_keys');
    }
}
