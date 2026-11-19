<?php

namespace App\Http\Requests\Pagos;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class GuardarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pago = $this->route('pago');

        return $pago instanceof Pago ? $this->user()?->can('update', $pago) : $this->user()?->can('create', Pago::class);
    }

    public static function reglas(bool $edicion): array
    {
        return [
            'pedido_id' => ['required', 'integer', 'exists:pedidos,id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'importe' => ['required', 'regex:/^\d{1,16}(?:\.\d{1,2})?$/D'],
            'medio' => ['required', 'in:efectivo,qr'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'clave_registro' => $edicion ? ['prohibited'] : ['required', 'uuid'],
            'version' => $edicion ? ['required', 'integer', 'min:1', 'max:4294967295'] : ['prohibited'],
            'motivo' => $edicion ? ['required', 'string', 'min:5', 'max:1000'] : ['prohibited'],
        ];
    }

    public function rules(): array
    {
        return self::reglas($this->route('pago') instanceof Pago);
    }

    protected function passedValidation(): void
    {
        if (array_diff(array_keys($this->except('_token', '_method', 'desde', 'volver_pedido')), array_keys($this->rules()))) {
            throw ValidationException::withMessages(['solicitud' => 'La solicitud contiene campos de pago no permitidos.']);
        }
    }
}
