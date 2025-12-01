<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Mail\ResetPasswordMobileMail;
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
use Illuminate\Support\Facades\Validator;
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

            // Obtener y asignar el rol_id del usuario (igual que en login normal)
            $role_id = DB::table('usuario_roles')
                ->where('usuario_id', $user->id)
                ->value('rol_id');

            $user->role_id = $role_id;

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

        $rules = [
            'email' => ['required', 'email', 'exists:users,email'],
        ];

        $messages = [
            'email.required' => 'El correo es requerido',
            'email.email' => 'El correo no es válido',
            'email.exists' => 'El correo no está registrado en el sistema',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');

        try {

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

        $rules = [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ];

        $messages = [
            'email.required' => 'El correo es requerido',
            'email.email' => 'El correo no es válido',
            'token.required' => 'El token es requerido',
            'token.string' => 'El token debe ser una cadena de texto',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $token = $request->input('token');

        try {

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

        $rules = [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];

        $messages = [
            'email.required' => 'El correo es requerido',
            'email.email' => 'El correo no es válido',
            'token.required' => 'El token es requerido',
            'token.string' => 'El token debe ser una cadena de texto',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password_confirmation.required' => 'La confirmación de contraseña es requerida',
            'password_confirmation.string' => 'La confirmación de contraseña debe ser una cadena de texto',
        ];


        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $token = $request->input('token');
        $password = $request->input('password');

        try {

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

    /**
     * Solicitar restablecimiento de contraseña para apps móviles
     * Genera un código de 6 dígitos y envía email solo con el código
     */
    public function forgotPasswordMobile(Request $request): JsonResponse {
        // Reglas de validación
        $rules = [
            'email' => ['required', 'email', 'exists:users,email'],
        ];

        // Mensajes personalizados
        $messages = [
            'email.required' => 'El correo es requerido',
            'email.email' => 'El correo no es válido',
            'email.exists' => 'El correo no está registrado en el sistema',
        ];

        // Validación de entrada
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');

        try {
            // Obtener el usuario
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'El correo no está registrado',
                    'success' => false
                ], 404);
            }

            // Verificar que el usuario esté activo
            if ($user->estado !== 'activo') {
                return response()->json([
                    'message' => 'Esta cuenta está inactiva o suspendida',
                    'success' => false
                ], 403);
            }

            // Generar código de 6 dígitos y token largo
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $token = Str::random(64);

            // Eliminar tokens anteriores para este email
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            // Crear nuevo registro con código y token
            DB::table('password_reset_tokens')
                ->insert([
                    'email' => $email,
                    'token' => $token,
                    'code' => $code,
                    'created_at' => Carbon::now(),
                ]);

            // Enviar email solo con el código (para apps móviles)
            Mail::to($email)->send(new ResetPasswordMobileMail($code, $user->nombre_completo));

            Log::info('Reset password code generado (mobile)', [
                'email' => $email,
                'timestamp' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un código de verificación a tu correo'
            ]);

        } catch (Exception $e) {
            Log::error('Error en forgot password mobile', [
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
     * Verificar código de verificación y retornar token largo
     * Para apps móviles: El usuario ingresa el código de 6 dígitos
     * y el sistema retorna el token largo que necesita para resetear
     */
    public function verifyResetCode(Request $request): JsonResponse {
        // Reglas de validación
        $rules = [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ];

        // Mensajes personalizados
        $messages = [
            'email.required' => 'El correo es requerido',
            'email.email' => 'El correo no es válido',
            'code.required' => 'El código es requerido',
            'code.string' => 'El código debe ser una cadena de texto',
            'code.size' => 'El código debe tener exactamente 6 dígitos',
        ];

        // Validación de entrada
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $code = $request->input('code');

        try {
            // Buscar el registro del código
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('code', $code)
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'message' => 'El código de verificación es inválido',
                    'success' => false
                ], 400);
            }

            // Verificar que el código no haya expirado (1 hora = 3600 segundos)
            $tokenAge = Carbon::now()->diffInSeconds(Carbon::parse($passwordReset->created_at));

            if ($tokenAge > 3600) {
                // Eliminar código expirado
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->where('code', $code)
                    ->delete();

                return response()->json([
                    'message' => 'El código de verificación ha expirado. Por favor, solicita uno nuevo',
                    'success' => false
                ], 400);
            }

            // Retornar el token largo para usar en reset-password
            return response()->json([
                'success' => true,
                'message' => 'Código verificado correctamente',
                'token' => $passwordReset->token
            ]);

        } catch (Exception $e) {
            Log::error('Error al verificar código', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al verificar el código',
                'success' => false
            ], 500);
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     * Requiere: token Bearer, contraseña actual, nueva contraseña
     * Revoca todos los tokens excepto el actual por seguridad
     */
    public function changePassword(Request $request): JsonResponse {
        // Reglas de validación
        $rules = [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', new \App\Rules\PasswordFormatRule()],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ];

        // Mensajes personalizados
        $messages = [
            'current_password.required' => 'La contraseña actual es requerida',
            'current_password.string' => 'La contraseña actual debe ser una cadena de texto',
            'new_password.required' => 'La nueva contraseña es requerida',
            'new_password.string' => 'La nueva contraseña debe ser una cadena de texto',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres',
            'new_password_confirmation.required' => 'La confirmación de contraseña es requerida',
            'new_password_confirmation.same' => 'Las contraseñas no coinciden',
        ];

        // Validación de entrada
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            // Verificar que la contraseña actual sea correcta
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 401);
            }

            // Actualizar la contraseña
            $user->password = Hash::make($request->input('new_password'));
            $user->save();

            // Obtener el token actual para no revocarlo
            $currentToken = $request->user()->token();

            // Revocar todos los tokens del usuario excepto el actual
            $request->user()->tokens()
                ->where('id', '!=', $currentToken->id)
                ->update(['revoked' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Tu contraseña ha sido cambiada exitosamente. Tus otras sesiones han sido cerradas por seguridad.'
            ]);

        } catch (Exception $e) {
            Log::error('Error al cambiar contraseña', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al cambiar la contraseña',
                'success' => false
            ], 500);
        }
    }
}
