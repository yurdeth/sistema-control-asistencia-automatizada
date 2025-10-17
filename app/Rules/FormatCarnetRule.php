<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FormatCarnetRule implements ValidationRule {
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        if (!preg_match('/^[A-Z]{2}\d{5}$/', $value)) {
            $fail("El campo :attribute no tiene el formato correcto: AB12345");
        }
    }
}
