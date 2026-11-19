<?php

namespace App\Http\Requests\Pedidos;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ValidarCierrePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pedido = $this->route('pedido');
        $capacidad = match ($this->route()->getName()) {
            'admin.fabricacion.validar' => 'validarMateriales',
            'admin.fabricacion.terminar' => 'terminar',
            'admin.pedidos.entregar' => 'entregar',
            default => null,
        };

        return $capacidad && $pedido instanceof Pedido && $this->user()?->can($capacidad, $pedido);
    }

    public static function reglas(string $accion): array
    {
        $reglas = ['version' => 'required|integer|min:1', 'confirmacion' => 'required|accepted'];
        if ($accion === 'terminar') {
            $reglas['clave_operacion'] = 'required|uuid';
            $reglas['confirmar_precio'] = 'required|accepted';
            $reglas['validar_materiales'] = 'sometimes|boolean';
        } elseif ($accion === 'entregar') {
            $reglas['clave_operacion'] = 'required|uuid';
            $reglas['observaciones'] = 'nullable|string|max:2000';
        }

        return $reglas;
    }

    public function rules(): array
    {
        $accion = match ($this->route()->getName()) {
            'admin.fabricacion.validar' => 'validar',
            'admin.fabricacion.terminar' => 'terminar',
            default => 'entregar',
        };

        return self::reglas($accion);
    }

    protected function passedValidation(): void
    {
        if (array_diff(array_keys($this->except('_token', '_method')), array_keys($this->rules()))) {
            throw ValidationException::withMessages(['solicitud' => 'La solicitud contiene campos no permitidos.']);
        }
    }
}
