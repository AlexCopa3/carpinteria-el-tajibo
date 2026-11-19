<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pagos\GuardarPagoRequest;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\PagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PagoController extends Controller
{
    public function index(Request $request, PagoService $service)
    {
        $this->authorize('viewAny', Pago::class);
        $datos = $request->validate(['q' => 'nullable|string|max:200', 'pedido_id' => 'nullable|integer|exists:pedidos,id', 'medio' => 'nullable|in:efectivo,qr']);
        $consulta = Pedido::with('cliente');
        if (! empty($datos['q'])) {
            $busqueda = trim($datos['q']);
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('datos_cliente_acordados->nombre', 'like', '%'.$busqueda.'%')
                    ->orWhereHas('cliente', fn ($cliente) => $cliente->where('nombre', 'like', '%'.$busqueda.'%'));
                if (ctype_digit($busqueda)) {
                    $consulta->orWhere('id', $busqueda);
                }
            });
        }
        if (! empty($datos['pedido_id'])) {
            $consulta->whereKey($datos['pedido_id']);
        }
        if (! empty($datos['medio'])) {
            $consulta->whereHas('pagos', fn ($pagos) => $pagos->where('medio', $datos['medio']));
        }
        $pedidos = $consulta->latest('id')->paginate(12)->withQueryString();
        $resumenes = [];
        foreach ($pedidos as $pedido) {
            $resumenes[$pedido->id] = $service->resumen($request->user(), $pedido);
        }

        return view('admin.pagos.index', compact('pedidos', 'resumenes'));
    }

    public function pedido(Request $request, Pedido $pedido, PagoService $service)
    {
        $this->authorize('viewAny', Pago::class);
        $pedido->load('cliente');
        $resumen = $service->resumen($request->user(), $pedido);
        $pagos = $pedido->pagos()->with('registradoPor')->latest('id')->paginate(12)->withQueryString();

        return view('admin.pagos.pedido', compact('pedido', 'resumen', 'pagos'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Pago::class);
        $datos = $request->validate(['pedido_id' => 'nullable|integer|exists:pedidos,id']);
        $pago = new Pago;
        $pago->pedido_id = $datos['pedido_id'] ?? null;
        $pago->fecha = today();
        $pago->clave_registro = (string) Str::uuid();
        $pedidos = Pedido::with('cliente')->latest('id')->get();

        return view('admin.pagos.create', compact('pago', 'pedidos'));
    }

    public function store(GuardarPagoRequest $request, PagoService $service)
    {
        $pago = $service->guardar($request->user(), $request->validated());

        return redirect()->to($this->retorno($request, $pago))->with(['mensaje' => 'Pago registrado. El saldo se calculó con los pagos guardados.', 'icono' => 'success']);
    }

    public function show(Request $request, Pago $pago, PagoService $service)
    {
        $this->authorize('view', $pago);
        $pago->load(['pedido.cliente', 'registradoPor']);
        $resumen = $service->resumen($request->user(), $pago->pedido);

        return view('admin.pagos.show', compact('pago', 'resumen'));
    }

    public function edit(Pago $pago)
    {
        $this->authorize('update', $pago);
        $pedidos = Pedido::with('cliente')->latest('id')->get();

        return view('admin.pagos.edit', compact('pago', 'pedidos'));
    }

    public function update(GuardarPagoRequest $request, Pago $pago, PagoService $service)
    {
        $pago = $service->guardar($request->user(), $request->validated(), $pago);

        return redirect()->to($this->retorno($request, $pago))->with(['mensaje' => 'Pago actualizado. La corrección y sus responsables quedaron registrados.', 'icono' => 'success']);
    }

    private function retorno(Request $request, Pago $pago): string
    {
        if ($request->query('desde') === 'pagos') {
            return route('admin.pagos.pedido', [$pago->pedido_id, 'desde' => $request->query('volver_pedido') === '1' ? 'pedido' : null]);
        }

        return route('admin.pagos.show', [$pago, 'desde' => $request->query('desde') === 'pedido' ? 'pedido' : null]);
    }
}
