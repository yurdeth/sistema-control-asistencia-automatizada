<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el usuario tiene un token válido de Passport
 * en las rutas web con Inertia
 */
class CheckPassportToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado con Passport (auth:api)
        if (!Auth::guard('api')->check()) {
            // Guardar la ruta solicitada (solo el path, sin el dominio completo)
            $intendedPath = $request->path();

            // Redirigir a login con el parámetro redirect
            return redirect('/login?redirect=' . urlencode('/' . $intendedPath));
        }

        return $next($request);
    }
}
