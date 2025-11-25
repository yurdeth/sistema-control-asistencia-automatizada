<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para redirigir usuarios ya autenticados con Passport
 * que intentan acceder a rutas de invitados (como /login)
 *
 * Este middleware actúa ANTES de que Inertia prepare la respuesta
 * para evitar el flash de contenido no autorizado
 */
class RedirectIfAuthenticatedPassport
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado con Passport
        if (Auth::guard('api')->check()) {
            if ($request->header('X-Inertia')) {
                return redirect('/dashboard', 303);
            }

            return redirect('/dashboard');
        }

        return $next($request);
    }
}
