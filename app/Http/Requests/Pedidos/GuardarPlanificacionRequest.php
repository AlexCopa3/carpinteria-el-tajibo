<?php

namespace App\Http\Requests\Pedidos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class GuardarPlanificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public static function reglas(): array
    {
        return ['version' => 'required|integer|min:1', 'motivo' => 'required|string|max:2000', 'materiales' => 'required|array|min:1|max:500', 'materiales.*' => 'array:id,detalle_pedido_id,material_id,cantidad_planificada,observaciones', 'materiales.*.id' => 'nullable|integer|distinct', 'materiales.*.detalle_pedido_id' => 'required|integer', 'materiales.*.material_id' => 'required|integer|exists:materiales,id', 'materiales.*.cantidad_planificada' => 'required', 'materiales.*.observaciones' => 'nullable|string|max:2000'];
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
