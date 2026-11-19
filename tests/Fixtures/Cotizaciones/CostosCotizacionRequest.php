<?php

namespace Tests\Fixtures\Cotizaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class CostosCotizacionRequest extends FormRequest
{
    protected function reglasCostos(bool $crear): array
    {
        $presencia = $crear ? 'required' : 'sometimes';
        $importe = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'];

        return [
            'costo_materiales' => [$presencia, ...$importe],
            'costo_mano_obra' => [$presencia, ...$importe],
            'otros_costos' => ['sometimes', ...$importe],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $permitidos = [...array_keys($this->rules()), '_token', '_method'];
            foreach (array_diff(array_keys($this->all()), $permitidos) as $campo) {
                $validator->errors()->add($campo, 'Este campo no se permite en esta operación.');
            }
        }];
    }
}
