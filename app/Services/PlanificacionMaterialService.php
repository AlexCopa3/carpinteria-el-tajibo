<?php

namespace App\Services;

use App\Http\Requests\Pedidos\GuardarPlanificacionRequest;
use App\Models\Material;
use App\Models\MaterialPedido;
use App\Models\Pedido;
use App\Models\User;
use App\Policies\PlanificacionMaterialPolicy;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class PlanificacionMaterialService
{
    public function guardar(User $actor, Pedido $pedido, array $datos): Pedido
    {
        $datos = PedidoService::validar($datos, GuardarPlanificacionRequest::reglas());

        return DB::transaction(function () use ($actor, $pedido, $datos) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            abort_unless((new PlanificacionMaterialPolicy)->update($actor->fresh(), $pedido), 403);
            app(PedidoService::class)->version($pedido, $datos['version']);
            $detalles = $pedido->detalles()->pluck('id')->all();
            $previos = MaterialPedido::whereIn('detalle_pedido_id', $detalles)->lockForUpdate()->get()->keyBy('id');
            $conservados = [];
            $datosRevisadosCambiaron = false;
            $antes = $previos->values()->toArray();
            foreach ($datos['materiales'] as $indice => $entrada) {
                $campo = 'materiales.'.$indice;
                if (! in_array((int) $entrada['detalle_pedido_id'], $detalles, true)) {
                    PedidoService::error($campo, 'El detalle no pertenece al pedido.');
                }
                $fila = ! empty($entrada['id']) ? $previos->get((int) $entrada['id']) : new MaterialPedido;
                if (! $fila || ($fila->exists && $fila->detalle_pedido_id !== (int) $entrada['detalle_pedido_id'])) {
                    PedidoService::error($campo, 'La fila no pertenece a este detalle.');
                }
                $material = Material::with('unidad')->whereKey($entrada['material_id'])->lockForUpdate()->firstOrFail();
                if ($fila->exists && $fila->material_id !== $material->id) {
                    PedidoService::error($campo.'.material_id', 'Para sustituir un material, deja su cantidad en 0 y añade otra fila.');
                }
                if (! $fila->exists && ! $material->activo) {
                    PedidoService::error($campo.'.material_id', 'Selecciona un material activo.');
                }
                if ($fila->exists && $fila->unidad_id !== $material->unidad_id) {
                    PedidoService::error($campo, 'La unidad ya no coincide con su historial.');
                }
                $fila->cantidad_planificada = (string) Decimal::cantidad($entrada['cantidad_planificada'], $material->unidad, $campo.'.cantidad_planificada', false);
                if (! $fila->exists) {
                    $fila->detalle_pedido_id = $entrada['detalle_pedido_id'];
                    $fila->material_id = $material->id;
                    $fila->unidad_id = $material->unidad_id;
                    $fila->material_nombre = $material->nombre;
                    $fila->unidad_nombre = $material->unidad->nombre;
                    $fila->componente = 'General';
                }
                $fila->observaciones = $entrada['observaciones'] ?? null;
                if (! $fila->exists || $fila->isDirty(['cantidad_planificada', 'observaciones'])) {
                    $datosRevisadosCambiaron = true;
                }
                $fila->actualizado_por_id = $actor->id;
                $fila->save();
                $conservados[] = $fila->id;
            }
            if (array_diff($previos->keys()->all(), $conservados)) {
                PedidoService::error('materiales', 'Conserva las filas originales; usa cantidad 0 para retirar una necesidad.');
            }
            if ($datosRevisadosCambiaron) {
                app(CierrePedidoService::class)->invalidarMateriales($actor, $pedido);
            }
            $pedido->version++;
            $pedido->save();
            app(AuditoriaService::class)->registrar($actor, $pedido, 'planificacion_modificada', ['materiales' => $antes], ['materiales' => $pedido->fresh()->detalles->flatMap->materiales->toArray(), 'motivo' => $datos['motivo'], 'version' => $pedido->version]);

            return $pedido;
        }, 5);
    }

    public function disponibilidad(Pedido $pedido): array
    {
        $filas = MaterialPedido::with('material.existencia')->whereIn('detalle_pedido_id', $pedido->detalles()->pluck('id'))->get();
        $resumen = [];
        foreach ($filas as $fila) {
            $clave = $fila->material_id.'-'.$fila->unidad_id;
            if (! isset($resumen[$clave])) {
                $resumen[$clave] = ['nombre' => $fila->material_nombre, 'unidad' => $fila->unidad_nombre, 'necesaria' => BigDecimal::zero(), 'existencia' => BigDecimal::of($fila->material?->existencia?->cantidad ?? '0')];
            }
            $resumen[$clave]['necesaria'] = $resumen[$clave]['necesaria']->plus($fila->cantidad_planificada);
        }
        foreach ($resumen as &$material) {
            $diferencia = $material['necesaria']->minus($material['existencia']);
            $material['comprar'] = $diferencia->isPositive() ? $diferencia : BigDecimal::zero();
        }

        return array_values($resumen);
    }
}
