<?php

namespace Tests\Fixtures\Cotizaciones;

use App\Models\Cotizacion;

class CrearCotizacionRequest extends CostosCotizacionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cotizacion::class) ?? false;
    }

    public function rules(): array
    {
        return $this->reglasCostos(true);
    }
}
