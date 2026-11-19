<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pedidos\GuardarPlanificacionRequest;
use App\Models\Material;
use App\Models\Pedido;
use App\Policies\PlanificacionMaterialPolicy;
use App\Services\PlanificacionMaterialService;
use Illuminate\Http\Request;

class PlanificacionMaterialController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((new PlanificacionMaterialPolicy)->viewAny($request->user()), 403);
        $datos = $request->validate(['q' => 'nullable|string|max:200']);
        $consulta = Pedido::with('cliente');
        if (! empty($datos['q'])) {
            $consulta->whereHas('cliente', fn ($cliente) => $cliente->where('nombre', 'like', '%'.$datos['q'].'%'));
        }
        $pedidos = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.planificacion.index', compact('pedidos'));
    }

    public function show(Pedido $pedido, PlanificacionMaterialService $service)
    {
        abort_unless((new PlanificacionMaterialPolicy)->view(auth()->user(), $pedido), 403);
        $pedido->load('detalles.detalleCotizacion', 'detalles.materiales.material');
        $pedido->load('detalles.materiales.usoRegistradoPor');
        $disponibilidad = $service->disponibilidad($pedido);

        return view('admin.planificacion.show', compact('pedido', 'disponibilidad'));
    }

    public function edit(Pedido $pedido)
    {
        abort_unless((new PlanificacionMaterialPolicy)->update(auth()->user(), $pedido), 403);
        $pedido->load('detalles.detalleCotizacion', 'detalles.materiales.material');
        $materiales = Material::with('unidad')->where('activo', true)->orderBy('nombre')->get();

        return view('admin.planificacion.edit', compact('pedido', 'materiales'));
    }

    public function update(GuardarPlanificacionRequest $request, Pedido $pedido, PlanificacionMaterialService $service)
    {
        $pedido = $service->guardar($request->user(), $pedido, $request->validated());

        return redirect()->route('admin.planificacion.show', $pedido)->with(['mensaje' => 'Planificación actualizada sin modificar existencias ni precios.', 'icono' => 'success']);
    }
}
