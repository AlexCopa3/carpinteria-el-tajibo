<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Services\PropuestaCasoService;
use App\Services\RecuperacionCasosService;
use App\Support\Acceso;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SimilitudController extends Controller
{
    public function show(Request $request, Cotizacion $cotizacion, DetalleCotizacion $detalle, RecuperacionCasosService $service)
    {
        $this->authorize('view', $cotizacion);
        abort_unless(Acceso::permite($request->user(), 'casos.consultar') && Acceso::permite($request->user(), 'costos.consultar'), 403);
        abort_unless($detalle->cotizacion_id === $cotizacion->id, 404);
        if ($request->query()) {
            throw ValidationException::withMessages(['solicitud' => 'La consulta no acepta características, precios o parámetros enviados por el navegador.']);
        }
        $resultado = $service->buscar($request->user(), $cotizacion, $detalle);

        return view('admin.similitud.show', compact('cotizacion', 'detalle', 'resultado'));
    }

    /** Buscar desde registro/edición antes de guardar, sin exigir materiales o costos. */
    public function buscar(Request $request, RecuperacionCasosService $service, ?Cotizacion $cotizacion = null)
    {
        $this->authorize($cotizacion ? 'update' : 'create', $cotizacion ?? Cotizacion::class);
        abort_unless(Acceso::permite($request->user(), 'casos.consultar') && Acceso::permite($request->user(), 'costos.consultar'), 403);
        $datos = $request->validate([
            'mueble' => ['required', 'array'],
        ]);
        if (array_diff(array_keys($request->all()), ['_token', 'mueble'])) {
            throw ValidationException::withMessages(['solicitud' => 'No se admiten precios, permisos ni parámetros de comparación.']);
        }
        $resultado = $service->buscarFormulario($request->user(), $datos['mueble'], $cotizacion);

        $propuestas = app(PropuestaCasoService::class)->preparar($request->user(), $datos['mueble'], $resultado);

        return response()->json([
            'propuestas' => $propuestas,
            'estado' => $resultado['estado'],
            'cantidad' => count($resultado['resultados']),
            'html' => view('admin.similitud._resultados', ['resultado' => $resultado, 'enFormulario' => true, 'propuestas' => $propuestas])->render(),
        ]);
    }
}
