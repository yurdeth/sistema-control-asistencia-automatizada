<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestAuthMiddleware {
    public function handle(Request $request, Closure $next) {
        $guestKey = $request->header('X-Guest-Key');

        if ($guestKey !== env('GUEST_API_KEY')) {
            return response()->json([
                'message' => 'No autorizado. API key inválida.',
                'success' => false
            ], 401);
        }

        $guestUser = User::where('email', env('GUEST_EMAIL'))
            ->where('estado', 'activo')
            ->first();

        if (!$guestUser) {
            return response()->json([
                'message' => 'Usuario invitado no disponible',
                'success' => false
            ], 403);
        }

        // Autenticar al usuario guest manualmente
        Auth::setUser($guestUser);

        return $next($request);
    }
}
