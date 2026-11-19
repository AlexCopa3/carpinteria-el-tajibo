<?php

namespace Tests\Fixtures\Cotizaciones;

use App\Models\Cotizacion;

class ModificarCotizacionRequest extends CostosCotizacionRequest
{
    public function authorize(): bool
    {
        $cotizacion = $this->route('cotizacion');

        return $cotizacion instanceof Cotizacion
            && ($this->user()?->can('update', $cotizacion) ?? false);
    }

    public function rules(): array
    {
        return $this->reglasCostos(false);
    }
}
