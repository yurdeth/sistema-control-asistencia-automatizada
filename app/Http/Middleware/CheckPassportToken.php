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
            // Si es una petición de Inertia, redirigir
            if ($request->header('X-Inertia')) {
                return redirect('/login');
            }

            // Si es una petición normal, también redirigir
            return redirect('/login');
        }

        return $next($request);
    }
}
