<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller {
    public function loginAsGuest(): JsonResponse {
        if (Auth::check()) {
            return response()->json([
                'message' => 'Ya autenticado',
                'success' => false
            ], 400);
        }

        $credentials = [
            'email' => env('GUEST_EMAIL'),
            'password' => env('GUEST_PASSWORD')
        ];

        try {
            // Verificar que las credenciales de invitado estén configuradas
            if (!$credentials['email'] || !$credentials['password']) {
                return response()->json([
                    'message' => 'Las credenciales de invitado no están configuradas',
                    'success' => false
                ], 500);
            }

            // Verificar si el usuario existe
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return response()->json([
                    'message' => 'El usuario invitado no existe',
                    'success' => false
                ], 404);
            }

            // Verificar el estado del usuario antes de intentar login
            if ($user->estado !== 'activo') {
                return response()->json([
                    'message' => 'El usuario invitado no está activo',
                    'success' => false
                ], 403);
            }

            // Intentar autenticación
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Error en las credenciales de invitado',
                    'success' => false
                ], 401);
            }

            // Actualizar último acceso
            $user->ultimo_acceso = Carbon::now();
            $user->save();

            // Obtener rol del usuario
            $role_id = DB::table('usuario_roles')
                ->where('usuario_id', $user->id)
                ->value('rol_id');

            $user->role_id = $role_id;

            // Crear token de acceso
            $tokenResult = $user->createToken('Guest Access Token');
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addDays(30);
            $token->save();

            // Obtener información adicional del usuario
            $departamento_nombre = DB::table('departamentos')
                ->where('id', $user->departamento_id)
                ->value('nombre');

            $user->departamento_nombre = $departamento_nombre;

            $role_nombre = DB::table('roles')
                ->join('usuario_roles', 'roles.id', '=', 'usuario_roles.rol_id')
                ->where('usuario_roles.usuario_id', $user->id)
                ->value('nombre');

            $user->role_nombre = $role_nombre;

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión como invitado exitoso',
                'user' => $user,
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
            ]);

        } catch (Exception $e) {
            Log::error('Error en login de invitado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error en el servidor',
                'success' => false
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            // Verificar si el usuario existe antes de intentar autenticar
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return response()->json([
                    'message' => 'El correo electrónico no existe',
                    'success' => false
                ], 401);
            }

            // Verificar el estado del usuario antes de intentar login
            if ($user->estado !== 'activo') {
                return response()->json([
                    'message' => 'El usuario no está activo',
                    'success' => false
                ], 403);
            }

            // Intentar autenticación
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'La contraseña es incorrecta',
                    'success' => false
                ], 401);
            }

            // Actualizar último acceso
            $user->ultimo_acceso = Carbon::now();
            $user->save();

            // Obtener rol del usuario
            $role_id = DB::table('usuario_roles')
                ->where('usuario_id', $user->id)
                ->value('rol_id');

            $user->role_id = $role_id;

            // Crear token de acceso
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addDays(30);
            $token->save();

            // Obtener información adicional del usuario
            $departamento_nombre = DB::table('departamentos')
                ->where('id', $user->departamento_id)
                ->value('nombre');

            $user->departamento_nombre = $departamento_nombre;

            $role_nombre = DB::table('roles')
                ->join('usuario_roles', 'roles.id', '=', 'usuario_roles.rol_id')
                ->where('usuario_roles.usuario_id', $user->id)
                ->value('nombre');

            $user->role_nombre = $role_nombre;

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'user' => $user,
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
            ]);

        } catch (Exception $e) {
            Log::error('Error en login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error en el servidor',
                'success' => false
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'No autenticado',
                    'success' => false
                ], 401);
            }

            if (!$request->header('Authorization')) {
                return response()->json([
                    'message' => 'Token de autenticación no proporcionado',
                    'success' => false
                ], 401);
            }

            $request->user()->token()->revoke();

            return response()->json([
                'message' => 'Sesión cerrada correctamente',
                'success' => true
            ]);

        } catch (Exception $e) {
            Log::error('Error en logout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /*private function sendWelcomeMail(Request $request, User $user){
        $details = [
            'subject' => 'Bienvenido a Minerva RV Lab, '. $user->name . '.',
            'name' => 'Minerva RV Lab',
            'email' => $user->email,
            'message' => 'Es un gusto tenerte con nosotros'
        ];

        Mail::to($user->email)->send(new ContactFormMail($details));
    }*/

    public function verifyToken(Request $request): JsonResponse {
        try {
            // El middleware auth:api ya verificó el token
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Token inválido',
                    'success' => false
                ], 401);
            }

            // Verificar que el usuario siga activo
            if ($user->estado !== 'activo') {
                return response()->json([
                    'message' => 'Usuario inactivo',
                    'success' => false
                ], 403);
            }

            return response()->json([
                'success' => true,
                'user' => $user,
                'message' => 'Token válido'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al verificar token',
                'success' => false
            ], 401);
        }
    }
}
