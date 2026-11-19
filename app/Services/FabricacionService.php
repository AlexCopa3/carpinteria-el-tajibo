<?php

namespace App\Services;

use App\Http\Requests\Pedidos\GuardarMaterialesUtilizadosRequest;
use App\Models\Material;
use App\Models\MaterialPedido;
use App\Models\Pedido;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FabricacionService
{
    public function guardar(User $actor, Pedido $pedido, array $datos): Pedido
    {
        $datos = PedidoService::validar($datos, GuardarMaterialesUtilizadosRequest::reglas());

        return DB::transaction(function () use ($actor, $pedido, $datos) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('registrarUsoMateriales', $pedido);
            app(PedidoService::class)->version($pedido, $datos['version']);
            $detalles = $pedido->detalles()->pluck('id')->all();
            $previos = MaterialPedido::whereIn('detalle_pedido_id', $detalles)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $antes = $previos->values()->toArray();
            $conservados = [];
            $presentes = [];
            foreach ($previos as $fila) {
                $presentes[$fila->detalle_pedido_id.'-'.$fila->material_id] = true;
            }
            $cambios = [];
            $correccion = false;
            foreach ($datos['materiales'] as $indice => $entrada) {
                $campo = 'materiales.'.$indice;
                $detalleId = (int) $entrada['detalle_pedido_id'];
                if (! in_array($detalleId, $detalles, true)) {
                    PedidoService::error($campo.'.detalle_pedido_id', 'El mueble no pertenece a este pedido.');
                }
                $fila = ! empty($entrada['id']) ? $previos->get((int) $entrada['id']) : new MaterialPedido;
                if (! $fila || ($fila->exists && (int) $fila->detalle_pedido_id !== $detalleId)) {
                    PedidoService::error($campo.'.id', 'La fila no pertenece a este mueble.');
                }
                if ($fila->exists) {
                    $conservados[] = $fila->id;
                    if ((int) $fila->material_id !== (int) $entrada['material_id']) {
                        PedidoService::error($campo.'.material_id', 'Conserva el material original; añade otra fila si utilizaste uno diferente.');
                    }
                }
                $valor = $entrada['cantidad_utilizada'] ?? null;
                $valor = $valor === '' ? null : $valor;
                if (! $fila->exists && $valor === null) {
                    continue;
                }
                $material = Material::with('unidad')->whereKey($entrada['material_id'])->lockForUpdate()->firstOrFail();
                if ($fila->exists && (int) $fila->unidad_id !== (int) $material->unidad_id) {
                    PedidoService::error($campo.'.material_id', 'La unidad ya no coincide con el historial del material.');
                }
                if (! $fila->exists) {
                    if (! $material->activo) {
                        PedidoService::error($campo.'.material_id', 'Selecciona un material activo.');
                    }
                    $clave = $detalleId.'-'.$material->id;
                    if (isset($presentes[$clave])) {
                        PedidoService::error($campo.'.material_id', 'El material ya figura en este mueble. Registra su cantidad en la fila existente.');
                    }
                    $presentes[$clave] = true;
                    $fila->detalle_pedido_id = $detalleId;
                    $fila->material_id = $material->id;
                    $fila->unidad_id = $material->unidad_id;
                    $fila->material_nombre = $material->nombre;
                    $fila->unidad_nombre = $material->unidad->nombre;
                    $fila->componente = 'General';
                    $fila->cantidad_inicial = null;
                    $fila->cantidad_planificada = '0';
                    $fila->actualizado_por_id = $actor->id;
                }
                $cantidad = $valor === null ? null : (string) Decimal::cantidad($valor, $material->unidad, $campo.'.cantidad_utilizada', false);
                $anterior = $fila->cantidad_utilizada;
                if ($fila->exists && $anterior !== null && $anterior !== $cantidad) {
                    $correccion = true;
                }
                $observacion = array_key_exists('observaciones_uso', $entrada) ? trim((string) $entrada['observaciones_uso']) : $fila->observaciones_uso;
                $fila->cantidad_utilizada = $cantidad;
                $fila->observaciones_uso = $observacion === '' ? null : $observacion;
                if (! $fila->exists || $fila->isDirty(['cantidad_utilizada', 'observaciones_uso'])) {
                    $fila->uso_registrado_por_id = $cantidad === null ? null : $actor->id;
                    $fila->uso_registrado_at = $cantidad === null ? null : now();
                    $cambios[] = $fila;
                }
            }
            if (array_diff($previos->keys()->all(), $conservados)) {
                PedidoService::error('materiales', 'Conserva todas las filas existentes. Vacío indica pendiente y 0 indica no utilizado.');
            }
            $motivo = trim((string) ($datos['motivo_correccion'] ?? ''));
            if ($correccion && mb_strlen($motivo) < 5) {
                PedidoService::error('motivo_correccion', 'Explica la corrección de cantidades ya registradas, con al menos 5 caracteres.');
            }
            if ($cambios === []) {
                $cotizacion = app(CotizacionService::class)->sincronizarUso($actor, $pedido);
                if ($cotizacion) {
                    $pedido->setRelation('cotizacionUso', $cotizacion);
                }

                return $pedido;
            }
            foreach ($cambios as $fila) {
                $fila->save();
            }
            app(CierrePedidoService::class)->invalidarMateriales($actor, $pedido);
            $versionAnterior = $pedido->version;
            $pedido->version++;
            $pedido->save();
            app(AuditoriaService::class)->registrar($actor, $pedido, 'uso_materiales_registrado',
                ['materiales' => $antes, 'version' => $versionAnterior],
                ['materiales' => MaterialPedido::whereIn('detalle_pedido_id', $detalles)->orderBy('id')->get()->toArray(), 'version' => $pedido->version, 'motivo_correccion' => $motivo === '' ? null : $motivo]);

            $cotizacion = app(CotizacionService::class)->sincronizarUso($actor, $pedido);
            if ($cotizacion) {
                $pedido->setRelation('cotizacionUso', $cotizacion);
            }

            return $pedido;
        }, 5);
    }
}
