<?php

namespace Tests\Fixtures\Cotizaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CotizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        abort_unless($request->user()?->can('view', $this->resource), 403);

        // Lista explícita: no serializar relaciones ni atributos administrativos por accidente.
        $datos = ['id' => $this->id, 'precio_final' => $this->precio_final];
        if ($request->user()->hasPermissionTo('costos.consultar', 'web')) {
            $datos += [
                'costo_materiales' => $this->costo_materiales,
                'costo_mano_obra' => $this->costo_mano_obra,
                'otros_costos' => $this->otros_costos,
            ];
        }
        // El mismo permiso efectivo utilizado por la aplicación.
        {
            if ($request->user()->hasPermissionTo('margenes.consultar', 'web')) {
                $datos['margen'] = $this->margen;
            }
            if ($request->user()->hasPermissionTo('ganancias.consultar', 'web')) {
                $datos['ganancia'] = $this->ganancia;
            }
        }

        return $datos;
    }
}
