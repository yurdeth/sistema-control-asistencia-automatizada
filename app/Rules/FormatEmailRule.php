<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FormatEmailRule implements ValidationRule {
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@ues\.edu\.sv$/', $value)) {
            $fail('El campo :attribute debe ser un correo electrónico válido que termine en @ues.edu.sv.');
        }
    }
}
