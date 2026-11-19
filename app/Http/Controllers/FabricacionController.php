<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pedidos\GuardarMaterialesUtilizadosRequest;
use App\Http\Requests\Pedidos\ValidarCierrePedidoRequest;
use App\Models\Material;
use App\Models\Pedido;
use App\Services\CierrePedidoService;
use App\Services\FabricacionService;

class FabricacionController extends Controller
{
    public function edit(Pedido $pedido)
    {
        $this->authorize('registrarUsoMateriales', $pedido);
        $pedido->load(['detalles.detalleCotizacion', 'detalles.materiales.material', 'detalles.materiales.usoRegistradoPor']);
        $materiales = Material::select('id', 'nombre', 'unidad_id')->with('unidad')->where('activo', true)->orderBy('nombre')->get();

        return view('admin.fabricacion.edit', compact('pedido', 'materiales'));
    }

    public function validar(ValidarCierrePedidoRequest $request, Pedido $pedido, CierrePedidoService $service)
    {
        $pedido = $service->validarMateriales($request->user(), $pedido, $request->validated());

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => 'Materiales revisados y validados.', 'icono' => 'success']);
    }

    public function terminar(ValidarCierrePedidoRequest $request, Pedido $pedido, CierrePedidoService $service)
    {
        $pedido = $service->terminar($request->user(), $pedido, $request->validated());

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => 'Fabricación terminada. El precio acordado y las existencias se conservaron.', 'icono' => 'success']);
    }

    public function update(GuardarMaterialesUtilizadosRequest $request, Pedido $pedido, FabricacionService $service)
    {
        $versionAnterior = $pedido->version;
        $pedido = $service->guardar($request->user(), $pedido, $request->validated());

        $mensaje = $pedido->version === $versionAnterior ? 'Sin cambios. Las cantidades vacías continúan pendientes.' : 'Materiales utilizados guardados. El pedido sigue en fabricación y las existencias no cambiaron.';

        if ($pedido->relationLoaded('cotizacionUso')) {
            $mensaje = 'Materiales utilizados guardados. Las cantidades y costos están reflejados en la cotización; revisa sus costos y aprueba el nuevo precio antes de aplicarlo al pedido.';
        }
        if ($pedido->relationLoaded('avisoCotizacion')) {
            $mensaje .= ' '.$pedido->getRelation('avisoCotizacion')->mensaje;
        }

        return redirect()->route('admin.pedidos.show', $pedido)->with(['mensaje' => $mensaje, 'icono' => $pedido->relationLoaded('avisoCotizacion') ? 'warning' : 'success']);
    }
}
