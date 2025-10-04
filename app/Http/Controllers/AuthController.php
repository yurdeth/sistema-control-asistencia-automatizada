<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller {
    public function login(Request $request): JsonResponse {
        if (!$request->email && !$request->password) {
            return response()->json([
                'message' => 'El correo electrónico y la contraseña son obligatorios',
                'success' => false
            ], 400);
        }

        if (!$request->email){
            return response()->json([
                'message' => 'El correo electrónico es obligatorio',
                'success' => false
            ], 400);
        }

        if (!$request->password){
            return response()->json([
                'message' => 'La contraseña es obligatoria',
                'success' => false
            ], 400);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            if ($request->user()) {
                return response()->json([
                    'message' => 'Ya has iniciado sesión',
                    'user' => $request->user(),
                ], 200);
            }

            $user = User::where('email', $credentials['email'])->first();
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'message' => 'Credenciales inválidas',
                    'success' => false
                ], 401);
            }

            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addDays(30);
            $token->save();

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
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse {
        try {
            if (!$request->user()) {
                return response()->json([
                    'message' => 'No autenticado',
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
}
