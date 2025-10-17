<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordFormatRule implements ValidationRule {
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        // Contar caracteres especiales
        $specials = preg_match_all('/[^A-Za-z0-9]/', $value);

        // Contar números
        $numbers = preg_match_all('/[0-9]/', $value);

        // Chequear mayúscula
        $uppercase = preg_match('/[A-Z]/', $value);

        // Chequear minúscula
        $lowercase = preg_match('/[a-z]/', $value);

        if ($specials < 2 || $numbers < 2 || !$uppercase || !$lowercase) {
            $fail("La contraseña debe contener al menos 2 caracteres especiales, 2 números, una mayúscula y una minúscula.");
        }
    }
}
