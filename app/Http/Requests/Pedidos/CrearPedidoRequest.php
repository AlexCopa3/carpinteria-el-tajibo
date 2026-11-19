<?php

namespace App\Http\Requests\Pedidos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CrearPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public static function reglas(): array
    {
        return [
            'cotizacion_id' => 'required|integer|exists:cotizaciones,id', 'clave_registro' => 'required|uuid',
            'aceptacion' => 'required|accepted', 'cliente_id' => 'nullable|integer|exists:clientes,id',
            'cliente' => 'nullable|array:nombre,tipo,telefono,email,direccion,observaciones',
            'cliente.nombre' => 'required_without:cliente_id|string|max:255',
            'cliente.tipo' => 'required_without:cliente_id|in:persona,empresa',
            'cliente.telefono' => 'required_without:cliente_id|string|max:40',
            'cliente.email' => 'nullable|email|max:255', 'cliente.direccion' => 'nullable|string|max:500', 'cliente.observaciones' => 'nullable|string|max:3000',
            'fecha_prevista_entrega' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'responsable_id' => 'nullable|integer|exists:users,id', 'observaciones' => 'nullable|string|max:3000',
        ];
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
