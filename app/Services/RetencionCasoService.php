<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\CasoHistorico;
use App\Models\MaterialCaso;
use App\Models\MaterialCotizacion;
use App\Models\Pedido;
use App\Models\User;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class RetencionCasoService
{
    public function retener(Pedido $pedido, ?User $actor = null): array
    {
        return DB::transaction(function () use ($pedido, $actor) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            if (! in_array($pedido->estado, ['terminado', 'entregado'], true) || ! $pedido->materiales_validados_at || ! $pedido->precio_confirmado_at
                || ! $pedido->materiales_validados_por_id || ! $pedido->precio_confirmado_por_id) {
                PedidoService::error('pedido', 'Solo se retienen pedidos terminados con sus validaciones finales.');
            }
            $evento = $pedido->estados()->where('estado_nuevo', 'terminado')->firstOrFail();
            $simulado = $this->esSimulado($pedido);
            $casos = [];
            foreach ($pedido->detalles()->with('detalleCotizacion', 'materiales')->orderBy('id')->get() as $detalle) {
                $existente = CasoHistorico::where('detalle_pedido_id', $detalle->id)->first();
                if ($existente) {
                    $casos[] = $existente;

                    continue;
                }
                $origen = $detalle->detalleCotizacion;
                $costosRevisados = $detalle->costosVigentes(true);
                $caso = new CasoHistorico;
                $caso->detalle_pedido_id = $detalle->id;
                foreach (['tipo_mueble_id', 'material_principal_id', 'tipo_nombre', 'material_principal_nombre', 'largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones', 'acabado'] as $campo) {
                    $caso->{$campo} = $origen->{$campo};
                }
                $caso->fecha_referencia = $evento->ocurrido_at->toDateString();
                $caso->cantidad_muebles = $detalle->cantidad_muebles;
                $caso->origen = $simulado ? 'simulado' : 'real';
                $caso->fuente = 'pedido';
                $caso->fuente_caracteristicas = 'cotizacion_acordada';
                $caso->fuente_costos = 'estimacion_cotizacion';
                $caso->mano_obra_estimada_total = $costosRevisados['mano_obra_total'];
                $caso->otros_costos_estimados_total = $costosRevisados['otros_costos_total'];
                $caso->costo_presupuestado_total = $origen->costo_total;
                $caso->precio_historico_total = $detalle->precioVigente(true);
                $caso->version = 1;
                $caso->estado_revision = 'pendiente';
                $lineas = [];
                $motivos = [];
                $suma = BigDecimal::zero();
                $costosCompletos = true;
                $principalUtilizado = false;
                $positivos = false;
                foreach ($detalle->materiales as $indice => $fila) {
                    if ($fila->cantidad_utilizada === null) {
                        $motivos[] = 'Hay cantidades utilizadas pendientes.';

                        continue;
                    }
                    $cantidad = BigDecimal::of($fila->cantidad_utilizada);
                    if ($cantidad->isNegative()) {
                        $motivos[] = 'Hay cantidades inválidas.';

                        continue;
                    }
                    $positivo = $cantidad->isPositive();
                    $positivos = $positivos || $positivo;
                    if ($fila->material_id === $caso->material_principal_id && $positivo) {
                        $principalUtilizado = true;
                    }
                    $cotizado = $fila->material_cotizacion_id ? MaterialCotizacion::whereKey($fila->material_cotizacion_id)->where('detalle_cotizacion_id', $origen->id)->where('material_id', $fila->material_id)->where('unidad_id', $fila->unidad_id)->first() : null;
                    $costo = $costosRevisados['materiales'][$fila->id] ?? null;
                    if ($costo !== null && ! BigDecimal::of($costo)->isPositive()) {
                        $costo = null;
                    }
                    $subtotal = $costo === null ? null : $cantidad->multipliedBy($costo)->toScale(2, RoundingMode::HALF_UP);
                    if ($subtotal !== null) {
                        $subtotal = Decimal::numero((string) $subtotal, 2, 'subtotal_estimado');
                    }
                    if ($positivo && $subtotal === null) {
                        $costosCompletos = false;
                    }
                    if ($subtotal !== null) {
                        $suma = $suma->plus($subtotal);
                    }
                    $lineas[] = ['posicion' => $indice + 1, 'material_pedido_id' => $fila->id, 'material_id' => $fila->material_id, 'unidad_id' => $fila->unidad_id, 'material_nombre' => $fila->material_nombre, 'unidad_nombre' => $fila->unidad_nombre, 'componente' => $fila->componente, 'cantidad_total' => $fila->cantidad_utilizada, 'costo_unitario_estimado' => $costo, 'subtotal_estimado' => $subtotal === null ? null : (string) $subtotal, 'fuente_costo' => $costo === null ? 'sin_estimacion_historica' : 'cotizacion_capturada'];
                }
                if (! $positivos) {
                    $motivos[] = 'No hay materiales utilizados con cantidad positiva.';
                }
                if (! $principalUtilizado) {
                    $motivos[] = 'El material principal acordado no figura utilizado.';
                }
                if (! $costosCompletos) {
                    $motivos[] = 'Hay materiales utilizados sin costo estimado histórico.';
                }
                $caso->motivo_no_elegible = $motivos ? implode(' ', array_unique($motivos)) : null;
                $caso->costo_materiales_estimado_total = $costosCompletos ? (string) $suma->toScale(2) : null;
                $caso->costo_estimado_total = $costosCompletos ? (string) Decimal::numero((string) $suma->plus($caso->mano_obra_estimada_total)->plus($caso->otros_costos_estimados_total)->toScale(2), 2, 'costo_estimado_total') : null;
                if (! $simulado && ! $caso->motivo_no_elegible) {
                    $caso->estado_revision = 'revisado';
                    $caso->materiales_revisados_por_id = $pedido->materiales_validados_por_id;
                    $caso->materiales_revisados_at = $pedido->materiales_validados_at;
                    $caso->precio_revisado_por_id = $pedido->precio_confirmado_por_id;
                    $caso->precio_revisado_at = $pedido->precio_confirmado_at;
                }
                $caso->save();
                foreach ($lineas as $linea) {
                    $material = new MaterialCaso;
                    $material->forceFill([...$linea, 'caso_historico_id' => $caso->id])->save();
                }
                app(AuditoriaService::class)->registrar($actor, $caso, 'caso_retenido', [], ['caso' => $caso->getAttributes(), 'materiales' => $lineas, 'revision' => $simulado ? 'Pendiente: origen simulado.' : 'Datos finales validados en el pedido; costos siguen siendo estimados.']);
                $casos[] = $caso;
            }

            return $casos;
        }, 5);
    }

    private function esSimulado(Pedido $pedido): bool
    {
        foreach (Auditoria::where('accion', 'datos_ejemplo_cargados')->get() as $registro) {
            $datos = $registro->datos_nuevos;
            if (($datos['origen'] ?? null) === 'simulado' && (in_array($pedido->id, $datos['ids']['pedidos'] ?? [], true) || in_array($pedido->cotizacion_id, $datos['ids']['cotizaciones'] ?? [], true))) {
                return true;
            }
        }

        return false;
    }
}
