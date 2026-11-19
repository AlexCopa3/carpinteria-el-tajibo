<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cotizaciones\AprobacionCotizacionRequest;
use App\Http\Requests\Cotizaciones\GuardarCotizacionRequest;
use App\Models\Cotizacion;
use App\Models\Material;
use App\Models\TipoMueble;
use App\Services\CotizacionPdfService;
use App\Services\CotizacionService;
use App\Services\EliminacionService;
use App\Services\PedidoService;
use App\Support\Acceso;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CotizacionController extends Controller
{
    /** Mostrar cotizaciones y filtros. */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Cotizacion::class);
        $datos = $request->validate(['q' => 'nullable|string|max:200', 'estado' => 'nullable|in:borrador,pendiente_costos,aprobada,rechazada']);
        $consulta = Cotizacion::with(['cliente', 'responsable', 'pedido']);
        if ($datos['q'] ?? null) {
            $busqueda = $datos['q'];
            $consulta->where(function ($consulta) use ($busqueda) {
                if (preg_match('/^\d+$/D', $busqueda)) {
                    $consulta->where('id', $busqueda);
                } else {
                    $consulta->whereRaw('1 = 0');
                }
            });
        }
        if ($datos['estado'] ?? null) {
            $consulta->where('estado', $datos['estado']);
        }
        $cotizaciones = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.cotizaciones.index', compact('cotizaciones'));
    }

    /** Mostrar el formulario de registro. */
    public function create()
    {
        $this->authorize('create', Cotizacion::class);
        abort_unless(Acceso::permite(auth()->user(), 'costos.consultar'), 403);
        $cotizacion = new Cotizacion;
        $cotizacion->clave_registro = Str::uuid()->toString();
        $tipos = TipoMueble::where('activo', true)->orderBy('id')->get();
        $materiales = Material::with('unidad')->where('activo', true)->orderBy('nombre')->get();
        $principales = Material::principales()->orderBy('nombre')->get();

        return view('admin.cotizaciones.create', compact('cotizacion', 'tipos', 'materiales', 'principales'));
    }

    /** Guardar los datos; el servicio realiza los cálculos y la auditoría. */
    public function store(GuardarCotizacionRequest $request, CotizacionService $service)
    {
        $cotizacion = $service->guardar($request->user(), $request->validated());

        return redirect()->route('admin.cotizaciones.show', [$cotizacion, 'desde' => $request->query('desde') === 'pedido' ? 'pedido' : null])->with([
            'mensaje' => 'Cotización registrada. El precio aún no está aprobado.',
            'icono' => 'success',
        ]);
    }

    /** Consultar la ficha, sus cálculos y la aprobación. */
    public function show(Cotizacion $cotizacion)
    {
        $this->authorize('view', $cotizacion);
        $cotizacion->load(['cliente', 'responsable', 'aprobador', 'detalles.materiales']);

        $rechazo = $cotizacion->estado === 'rechazada' ? $cotizacion->auditorias()->with('usuario')->where('accion', 'rechazada')->latest('id')->first() : null;

        return view('admin.cotizaciones.show', compact('cotizacion', 'rechazo'));
    }

    /** Mostrar la edición autorizada; guardar exige volver a aprobar si existe un pedido. */
    public function edit(Cotizacion $cotizacion)
    {
        $this->authorize('update', $cotizacion);
        abort_unless(Acceso::permite(auth()->user(), 'costos.consultar'), 403);
        $cotizacion->load('detalles.materiales');
        $tipos = TipoMueble::where('activo', true)->orderBy('id')->get();
        $materiales = Material::with('unidad')->where('activo', true)->orderBy('nombre')->get();
        $principales = Material::principales()->orderBy('nombre')->get();

        return view('admin.cotizaciones.edit', compact('cotizacion', 'tipos', 'materiales', 'principales'));
    }

    /** Actualizar sin perder la versión que se revisó. */
    public function update(GuardarCotizacionRequest $request, Cotizacion $cotizacion, CotizacionService $service)
    {
        $cotizacion = $service->guardar($request->user(), $request->validated(), $cotizacion);

        return redirect()->route('admin.cotizaciones.show', [$cotizacion, 'desde' => $request->query('desde') === 'pedido' ? 'pedido' : null])->with([
            'mensaje' => 'Cotización actualizada. Revisa los totales antes de aprobar.',
            'icono' => 'success',
        ]);
    }

    /** Una sola acción valida y aprueba todos los precios. */
    public function aprobar(AprobacionCotizacionRequest $request, Cotizacion $cotizacion, CotizacionService $service)
    {
        $cotizacion = $service->aprobar($request->user(), $cotizacion, $request->validated());

        return redirect()->route('admin.cotizaciones.show', [$cotizacion, 'desde' => $request->query('desde') === 'pedido' ? 'pedido' : null])->with([
            'mensaje' => 'Cotización validada y aprobada. Sus datos quedaron protegidos.',
            'icono' => 'success',
        ]);
    }

    public function destroy(Request $request, Cotizacion $cotizacion, EliminacionService $service)
    {
        $this->authorize('delete', $cotizacion);
        $datos = $request->validate(['version' => 'required|integer|min:1']);
        $service->eliminar($request->user(), $cotizacion, (int) $datos['version']);

        return redirect()->route('admin.cotizaciones.index')->with(['mensaje' => 'Cotización preliminar eliminada.', 'icono' => 'success']);
    }

    public function rechazar(Request $request, Cotizacion $cotizacion, CotizacionService $service)
    {
        $this->authorize('reject', $cotizacion);
        $datos = $request->validate(['version' => 'required|integer|min:1', 'motivo' => 'required|string|min:5|max:2000', 'confirmacion' => 'required|accepted']);
        if (array_diff(array_keys($request->except('_token', 'desde')), ['version', 'motivo', 'confirmacion'])) {
            PedidoService::error('solicitud', 'El rechazo contiene campos no permitidos.');
        }
        $cotizacion = $service->rechazar($request->user(), $cotizacion, $datos);

        return redirect()->route('admin.cotizaciones.show', $cotizacion)->with(['mensaje' => 'Cotización marcada como rechazada por el cliente. Sus datos se conservaron.', 'icono' => 'success']);
    }

    /** PDF comercial, sin costos internos o recargo. */
    public function pdf(Cotizacion $cotizacion, CotizacionPdfService $service)
    {
        $this->authorize('view', $cotizacion);
        abort_unless($cotizacion->aprobado_at !== null && $cotizacion->estado === 'aprobada', 403, 'Primero debe aprobarse la cotización.');

        return $service->generar(auth()->user(), $cotizacion);
    }
}
