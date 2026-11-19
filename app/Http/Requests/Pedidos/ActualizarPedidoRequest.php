<?php

namespace App\Http\Requests\Pedidos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ActualizarPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public static function reglas(): array
    {
        return ['version' => 'required|integer|min:1', 'fecha_prevista_entrega' => 'sometimes|nullable|date_format:Y-m-d', 'responsable_id' => 'sometimes|nullable|integer|exists:users,id', 'observaciones' => 'nullable|string|max:3000'];
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
