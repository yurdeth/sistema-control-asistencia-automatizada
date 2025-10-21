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
        try {
            // En esta verga, creo un usuario aleatorio temporalmente para el invitado
            $randomUser = User::create([
                'nombre_completo' => 'guest ' . uniqid(),
                'email' => 'guest_' . uniqid() . '@ues.edu.sv',
                'password' => bcrypt(uniqid()),
                'departamento_id' => null,
                'estado' => 'activo',
                'ultimo_acceso' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('usuario_roles')->insert([
                'usuario_id' => $randomUser->id,
                'rol_id' => 7,
                'asignado_por_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $randomUser->save();
            Auth::login($randomUser);
            $user = $randomUser;

            // Crear token de invitado
            $tokenResult = $user->createToken('Personal Access Token');
            $guestToken = $tokenResult->token;
            $guestToken->expires_at = Carbon::now()->addDays(30);
            $guestToken->save();

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'user' => $user,
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($guestToken->expires_at)->toDateTimeString(),
            ]);
        } catch (Exception $e) {
            Log::error('Error en login de invitado', [
                'error' => $e->getMessage(),
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
