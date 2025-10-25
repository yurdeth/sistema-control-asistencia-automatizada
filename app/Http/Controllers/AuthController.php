<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    /**
     * Solicitar restablecimiento de contraseña
     * Genera un token y envía un email con el link de reset
     */
    public function forgotPassword(Request $request): JsonResponse {
        try {
            $request->validate([
                'email' => ['required', 'email', 'exists:users,email'],
            ], [
                'email.required' => 'El correo es requerido',
                'email.email' => 'El correo no es válido',
                'email.exists' => 'El correo no está registrado en el sistema',
            ]);

            $email = $request->input('email');

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'El correo no está registrado',
                    'success' => false
                ], 404);
            }

            if ($user->estado !== 'activo') {
                return response()->json([
                    'message' => 'Esta cuenta está inactiva o suspendida',
                    'success' => false
                ], 403);
            }

            $token = Str::random(64);

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            DB::table('password_reset_tokens')
                ->insert([
                    'email' => $email,
                    'token' => $token,
                    'created_at' => Carbon::now(),
                ]);

            Mail::to($email)->send(new ResetPasswordMail($email, $token, $user->nombre_completo));

            Log::info('Reset password token generado', [
                'email' => $email,
                'timestamp' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un correo con las instrucciones para restablecer tu contraseña'
            ]);

        } catch (Exception $e) {
            Log::error('Error en forgot password', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'success' => false
            ], 500);
        }
    }

    /**
     * Validar que el token de reset sea válido y no haya expirado
     */
    public function validateResetToken(Request $request): JsonResponse {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'token' => ['required', 'string'],
            ]);

            $email = $request->input('email');
            $token = $request->input('token');

            // Buscar el registro del token
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('token', $token)
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'message' => 'El token de restablecimiento es inválido',
                    'success' => false
                ], 400);
            }

            // Verificar que el token no haya expirado
            $tokenAge = Carbon::now()->diffInSeconds(Carbon::parse($passwordReset->created_at));

            if ($tokenAge > 3600) {
                // Eliminar token expirado
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->where('token', $token)
                    ->delete();

                return response()->json([
                    'message' => 'El enlace de restablecimiento ha expirado. Por favor, solicita uno nuevo',
                    'success' => false
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token válido'
            ]);

        } catch (Exception $e) {
            Log::error('Error al validar reset token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al validar el token',
                'success' => false
            ], 500);
        }
    }

    /**
     * Restablecer la contraseña del usuario
     */
    public function resetPassword(Request $request): JsonResponse {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'token' => ['required', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'password.required' => 'La contraseña es requerida',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres',
                'password.confirmed' => 'Las contraseñas no coinciden',
            ]);

            $email = $request->input('email');
            $token = $request->input('token');
            $password = $request->input('password');

            // Validar que el token sea válido y no esté expirado
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('token', $token)
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'message' => 'El token de restablecimiento es inválido',
                    'success' => false
                ], 400);
            }

            // Verificar que el token no haya expirado
            $tokenAge = Carbon::now()->diffInSeconds(Carbon::parse($passwordReset->created_at));

            if ($tokenAge > 3600) {
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->where('token', $token)
                    ->delete();

                return response()->json([
                    'message' => 'El enlace de restablecimiento ha expirado. Por favor, solicita uno nuevo',
                    'success' => false
                ], 400);
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'El usuario no existe',
                    'success' => false
                ], 404);
            }

            $user->password = Hash::make($password);
            $user->save();

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('token', $token)
                ->delete();

            // Revocar todos los tokens de acceso existentes del usuario
            DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->where('revoked', false)
                ->update(['revoked' => true]);

            Log::info('Contraseña restablecida', [
                'user_id' => $user->id,
                'email' => $email,
                'timestamp' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tu contraseña ha sido restablecida exitosamente. Por favor inicia sesión con tu nueva contraseña'
            ]);

        } catch (Exception $e) {
            Log::error('Error en reset password', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al restablecer la contraseña',
                'success' => false
            ], 500);
        }
    }
}
