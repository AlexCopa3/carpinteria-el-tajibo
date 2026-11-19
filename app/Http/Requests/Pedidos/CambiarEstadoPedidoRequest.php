<?php

namespace App\Http\Requests\Pedidos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CambiarEstadoPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public static function reglas(): array
    {
        return ['version' => 'required|integer|min:1', 'estado' => 'required|in:en_fabricacion', 'clave_operacion' => 'required|uuid'];
    }

    public function rules(): array
    {
        return self::reglas();
    }

    protected function passedValidation(): void
    {
        $permitidos = array_filter(array_keys(self::reglas()), fn ($campo) => ! str_contains($campo, '.'));
        $extra = array_diff(array_keys($this->except('_token', '_method')), $permitidos);
        if ($extra) {
            throw ValidationException::withMessages(['solicitud' => 'La solicitud contiene campos no permitidos.']);
        }
    }
}
