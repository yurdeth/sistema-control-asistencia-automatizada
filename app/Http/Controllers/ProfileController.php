<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller {
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse {
        if (!Auth::check()) {
            return Redirect::route('login');
        }

        if (Auth::user()->id != $request->user()->id) {
            return Redirect::route('profile.edit')->with('error', 'No estás autorizado para realizar esta acción.');
        }

        try {
            $request->user()->fill($request->validated());

            if ($request->user()->isDirty('email')) {
                $request->user()->email_verified_at = null;
            }

            $request->user()->save();

            return Redirect::route('profile.edit');
        } catch (Exception $e) {
            return Redirect::route('profile.edit')->with('error', 'Ocurrió un error al actualizar el perfil: ' . $e->getMessage());
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse {
        if (!Auth::check()) {
            return Redirect::route('login');
        }

        if (Auth::user()->id != $request->user()->id) {
            return Redirect::route('profile.edit')->with('error', 'No estás autorizado para realizar esta acción.');
        }

        try {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();

            Auth::logout();

            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/');
        } catch (Exception $e) {
            return Redirect::route('profile.edit')->with('error', 'Ocurrió un error al eliminar la cuenta: ' . $e->getMessage());
        }
    }
}
