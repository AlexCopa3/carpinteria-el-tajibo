<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pedidos\ActualizarPedidoRequest;
use App\Http\Requests\Pedidos\CambiarEstadoPedidoRequest;
use App\Http\Requests\Pedidos\CrearPedidoRequest;
use App\Http\Requests\Pedidos\ValidarCierrePedidoRequest;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Services\CierrePedidoService;
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Support\Acceso;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Pedido::class);
        $datos = $request->validate(['q' => 'nullable|string|max:200', 'estado' => 'nullable|in:aceptado,en_fabricacion,terminado,entregado']);
        $consulta = Pedido::with('cliente');
        if (! empty($datos['q'])) {
            $busqueda = $datos['q'];
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->whereHas('cliente', fn ($cliente) => $cliente->where('nombre', 'like', '%'.$busqueda.'%'));
                if (preg_match('/^\d+$/D', $busqueda)) {
                    $consulta->orWhere('id', $busqueda);
                }
            });
        }
        if (! empty($datos['estado'])) {
            $consulta->where('estado', $datos['estado']);
        }
        $pedidos = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.pedidos.index', compact('pedidos'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Pedido::class);
        $this->authorize('viewAny', Cliente::class);
        $datos = $request->validate(['cotizacion' => 'required|integer|exists:cotizaciones,id']);
        $cotizacion = Cotizacion::with('detalles')->findOrFail($datos['cotizacion']);
        $this->authorize('view', $cotizacion);
        $existente = Pedido::where('cotizacion_id', $cotizacion->id)->first();
        if ($existente) {
            return redirect()->route('admin.pedidos.show', $existente);
        }
        abort_unless($cotizacion->estado === 'aprobada' && $cotizacion->aprobado_at && $cotizacion->precio_final !== null, 422, 'Primero debe aprobarse la cotización.');
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();
        $responsables = User::where('activo', true)->role(['propietario', 'maestro'])->orderBy('name')->get();
        $clave = Str::uuid()->toString();

        return view('admin.pedidos.create', compact('cotizacion', 'clientes', 'responsables', 'clave'));
    }

    public function store(CrearPedidoRequest $request, PedidoService $service)
    {
        $pedido = $service->crear($request->user(), $request->validated());

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => 'Pedido registrado. Los materiales están preparados para su revisión.', 'icono' => 'success']);
    }

    public function show(Pedido $pedido)
    {
        $this->authorize('view', $pedido);
        $pedido->load(['detalles.detalleCotizacion', 'estados.usuario', 'responsable', 'registradoPor', 'materialesValidadosPor', 'precioConfirmadoPor']);

        if (auth()->user()->can('verUsoMateriales', $pedido)) {
            $pedido->load('detalles.materiales.usoRegistradoPor');
        }

        if (Acceso::permite(auth()->user(), 'casos.consultar')) {
            $pedido->load('detalles.casoHistorico');
        }

        $resumenPagos = auth()->user()->can('viewAny', Pago::class)
            ? app(PagoService::class)->resumen(auth()->user(), $pedido) : null;

        $cotizacionPendiente = $pedido->cotizacion_aceptada_version !== $pedido->cotizacion->version && Acceso::permite(auth()->user(), 'cotizaciones.consultar') ? $pedido->cotizacion : null;

        return view('admin.pedidos.show', compact('pedido', 'resumenPagos', 'cotizacionPendiente'));
    }

    public function aceptarCotizacion(Request $request, Pedido $pedido, PedidoService $service)
    {
        $this->authorize('aceptarCotizacion', $pedido);
        $datos = $request->validate(['version' => 'required|integer|min:1', 'cotizacion_version' => 'required|integer|min:1', 'aceptacion' => 'required|accepted']);
        if (array_diff(array_keys($request->except('_token')), ['version', 'cotizacion_version', 'aceptacion'])) {
            PedidoService::error('solicitud', 'La confirmación contiene campos no permitidos.');
        }
        $pedido = $service->aceptarCotizacion($request->user(), $pedido, $datos);

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => 'Pedido actualizado con la cotización aceptada.', 'icono' => 'success']);
    }

    public function edit(Pedido $pedido)
    {
        $this->authorize('update', $pedido);
        $responsables = User::where('activo', true)->role(['propietario', 'maestro'])->orderBy('name')->get();

        return view('admin.pedidos.edit', compact('pedido', 'responsables'));
    }

    public function update(ActualizarPedidoRequest $request, Pedido $pedido, PedidoService $service)
    {
        $pedido = $service->actualizar($request->user(), $pedido, $request->validated());

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => 'Pedido actualizado.', 'icono' => 'success']);
    }

    public function entregar(ValidarCierrePedidoRequest $request, Pedido $pedido, CierrePedidoService $service)
    {
        $pedido = $service->entregar($request->user(), $pedido, $request->validated());

        $mensaje = 'Entrega registrada correctamente.';
        if ($request->user()->can('viewAny', Pago::class)) {
            $resumen = app(PagoService::class)->resumen($request->user(), $pedido);
            if (BigDecimal::of($resumen['saldo'])->isPositive()) {
                $mensaje .= ' Saldo pendiente según pagos registrados: Bs '.Decimal::mostrar($resumen['saldo'], 2).'.';
            }
        }

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => $mensaje, 'icono' => 'success']);
    }

    public function cambiarEstado(CambiarEstadoPedidoRequest $request, Pedido $pedido, PedidoService $service)
    {
        $pedido = $service->cambiarEstado($request->user(), $pedido, $request->validated());

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => 'Fabricación iniciada. No se descontaron existencias.', 'icono' => 'success']);
    }
}
