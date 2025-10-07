<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneRule implements ValidationRule {
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        if (!preg_match('/^(\+503\s)?([267])\d{3}(-)?\d{4}$/', $value)) {
            $fail("El campo $attribute no tiene un formato de número de teléfono válido");
        }
    }
}
