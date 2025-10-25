<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $email = $request->input('email');
            $token = $request->input('token');
            $password = $request->input('password');

            // Validar que el token sea válido y no esté expirado
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('token', $token)
                ->first();

            if (!$passwordReset) {
                throw ValidationException::withMessages([
                    'email' => ['El token de restablecimiento es inválido.'],
                ]);
            }

            // Verificar que el token no haya expirado
            $tokenAge = Carbon::now()->diffInSeconds(Carbon::parse($passwordReset->created_at));

            if ($tokenAge > 3600) {
                // Eliminar token expirado
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->where('token', $token)
                    ->delete();

                throw ValidationException::withMessages([
                    'email' => ['El enlace de restablecimiento ha expirado. Por favor, solicita uno nuevo.'],
                ]);
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['El usuario no existe.'],
                ]);
            }

            $user->password = Hash::make($password);
            $user->remember_token = Str::random(60);
            $user->save();

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('token', $token)
                ->delete();

            DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->where('revoked', false)
                ->update(['revoked' => true]);

            event(new PasswordReset($user));

            Log::info('Contraseña restablecida desde web', [
                'user_id' => $user->id,
                'email' => $email,
                'timestamp' => Carbon::now()
            ]);

            return redirect()->route('login')->with('status', 'Tu contraseña ha sido restablecida exitosamente.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en reset password web', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw ValidationException::withMessages([
                'email' => ['Error al restablecer la contraseña. Por favor, intenta de nuevo.'],
            ]);
        }
    }
}
