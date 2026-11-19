<?php

namespace Tests\Fixtures\Cotizaciones;

use App\Models\Cotizacion;

class AprobarCotizacionRequest extends CostosCotizacionRequest
{
    public function authorize(): bool
    {
        $cotizacion = $this->route('cotizacion');

        return $cotizacion instanceof Cotizacion
            && ($this->user()?->can('approve', $cotizacion) ?? false);
    }

    public function rules(): array
    {
        return ['precio_final' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99']];
    }
}
