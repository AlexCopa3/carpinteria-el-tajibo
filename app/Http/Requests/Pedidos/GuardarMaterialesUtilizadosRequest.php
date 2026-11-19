<?php

namespace App\Http\Requests\Pedidos;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class GuardarMaterialesUtilizadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pedido = $this->route('pedido');

        return $pedido instanceof Pedido && $this->user()?->can('registrarUsoMateriales', $pedido);
    }

    public static function reglas(): array
    {
        return [
            'version' => 'required|integer|min:1',
            'motivo_correccion' => 'nullable|string|max:2000',
            'materiales' => 'required|array|min:1|max:500',
            'materiales.*' => 'array:id,detalle_pedido_id,material_id,cantidad_utilizada,observaciones_uso',
            'materiales.*.id' => 'nullable|integer|distinct',
            'materiales.*.detalle_pedido_id' => 'required|integer',
            'materiales.*.material_id' => 'required|integer|exists:materiales,id',
            'materiales.*.cantidad_utilizada' => 'present|nullable',
            'materiales.*.observaciones_uso' => 'nullable|string|max:2000',
        ];
    }

    public function rules(): array
    {
        return self::reglas();
    }

    protected function passedValidation(): void
    {
        if (array_diff(array_keys($this->except('_token', '_method', 'desde', 'volver_pedido')), ['version', 'motivo_correccion', 'materiales'])) {
            throw ValidationException::withMessages(['solicitud' => 'La solicitud contiene campos no permitidos.']);
        }
    }
}
