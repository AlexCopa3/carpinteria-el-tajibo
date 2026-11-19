<?php

namespace App\Http\Requests\Cotizaciones;

use App\Models\Cotizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AprobacionCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cotizacion = $this->route('cotizacion');

        return $cotizacion instanceof Cotizacion
            && $this->user()?->can('approve', $cotizacion)
            && $this->user()?->can('validatePrice', $cotizacion);
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'precios' => ['required', 'array', 'min:1', 'max:20'],
            'precios.*' => ['required', 'regex:/^\d{1,16}(?:\.\d{1,2})?$/D'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach (array_diff(array_keys($this->all()), ['_token', 'version', 'precios', 'desde']) as $campo) {
                $validator->errors()->add($campo, 'Solo puedes confirmar la versión y los precios finales de cada mueble.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['precios.*' => 'precio final del mueble', 'version' => 'versión revisada'];
    }
}
