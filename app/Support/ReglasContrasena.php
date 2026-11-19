<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class ReglasContrasena
{
    public static function reglas(): array
    {
        return [
            'required', 'string', 'max:72', 'confirmed',
            Password::min(12)->mixedCase()->numbers()->symbols(),
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('La contraseña es demasiado larga; utiliza menos caracteres.');
                }
            },
        ];
    }
}
