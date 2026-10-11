<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxTextBytes implements ValidationRule
{
    public const MAX_BYTES = 65535;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Kapasitas kolom MySQL TEXT dihitung dalam byte, termasuk untuk UTF-8.
        if (is_string($value) && strlen($value) > self::MAX_BYTES) {
            $fail('Kolom :attribute tidak boleh melebihi 65535 byte.');
        }
    }
}
