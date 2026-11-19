<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CierrePedidoService;
use App\Services\PagoService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HistorialCarpinteriaSeeder extends Seeder
{
    public const LOTE = 'historial_carpinteria_2020_2026_v1';

    public const LOTE_ORDEN = 'historial_ids_cronologicos_v1';

    public const CORTE = '2026-10-05';

    public const LOTE_PERIODO = 'historial_hasta_octubre_2026_v1';

    public const LOTE_CORRELATIVOS = 'historial_ids_correlativos_v1';

    public const LOTE_DISTRIBUCION = 'historial_distribuido_marzo_2020_octubre_2026_v1';

    private const CORTE_ANTERIOR = '2026-11-17';

    public function run(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'historial_cargado')->where('datos_nuevos->lote', self::LOTE)->exists()) {
                return;
            }
            $lotes = Auditoria::where('accion', 'datos_ejemplo_cargados')->get()->filter(fn ($a) => in_array($a->datos_nuevos['lote'] ?? null, [DatosSistemaSeeder::PRIMERA_CARGA, DatosSistemaSeeder::SEGUNDA_CARGA, CasosHistoricosSeeder::LOTE_PEDIDOS], true));
            $ids = fn ($tabla) => $lotes->flatMap(fn ($a) => $a->datos_nuevos['ids'][$tabla] ?? [])->unique()->values()->all();
            $pedidos = Pedido::whereIn('id', $ids('pedidos'))->with('detalles')->orderBy('id')->lockForUpdate()->get();
            $clientes = Cliente::whereIn('id', $ids('clientes'))->orderBy('id')->get();
            $casos = DB::table('casos_historicos')->whereIn('detalle_pedido_id', $pedidos->flatMap->detalles->pluck('id'));
            if ($pedidos->count() !== 47 || $clientes->count() !== 36 || $casos->count() !== 87 || (clone $casos)->where('origen', '<>', 'simulado')->exists()) {
                throw new \RuntimeException('El historial requiere los 47 pedidos, 36 clientes y 87 casos del lote autorizado.');
            }
            $cierres = $pedidos->whereIn('estado', ['terminado', 'entregado']);
            $recientes = $cierres->where('estado', 'terminado')->sortByDesc('id')->take(3)->pluck('id')->all();
            $entregados = $cierres->reject(fn ($p) => in_array($p->id, $recientes, true))->values();
            if ($cierres->count() !== 39 || $entregados->count() !== 36 || count($recientes) !== 3) {
                throw new \RuntimeException('Los estados actuales difieren del historial esperado.');
            }
            foreach ($clientes as $indice => $cliente) {
                $correo = null;
                $telefono = '+591 '.(70010001 + $indice * 137 + ($indice % 4) * 1000000);
                DB::table('clientes')->where('id', $cliente->id)->update(['email' => $correo, 'telefono' => $telefono]);
            }
            $corte = Carbon::parse(self::CORTE_ANTERIOR)->setTime(16, 0);
            $inicio = Carbon::parse('2020-02-12')->setTime(16, 0);
            $ultimo = Carbon::parse('2026-10-20')->setTime(16, 0);
            $dias = (int) $inicio->diffInDays($ultimo);
            $fechas = [];
            foreach ($entregados as $indice => $pedido) {
                if ($pedido->estado === 'terminado') {
                    app(CierrePedidoService::class)->entregar($actor, $pedido, ['version' => $pedido->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true]);
                }
                $fin = $inicio->copy()->addDays((int) round($dias * $indice / 35));
                $fechas[$pedido->id] = ['aceptado' => $fin->copy()->subDays(14), 'en_fabricacion' => $fin->copy()->subDays(12), 'terminado' => $fin->copy()->subDays(2), 'entregado' => $fin];
            }
            foreach ($recientes as $indice => $id) {
                $fin = $corte->copy()->subDays($indice * 2);
                $fechas[$id] = ['aceptado' => $fin->copy()->subDays(12), 'en_fabricacion' => $fin->copy()->subDays(10), 'terminado' => $fin];
            }
            foreach ($pedidos->whereIn('estado', ['aceptado', 'en_fabricacion'])->values() as $indice => $pedido) {
                $aceptado = $corte->copy()->subDays($indice + 2);
                $fechas[$pedido->id] = ['aceptado' => $aceptado];
                if ($pedido->estado === 'en_fabricacion') {
                    $fechas[$pedido->id]['en_fabricacion'] = $aceptado->copy()->addDay();
                }
            }
            $cotizaciones = $ids('cotizaciones');
            foreach ($pedidos as $pedido) {
                $cronologia = $fechas[$pedido->id];
                $aceptado = $cronologia['aceptado'];
                $fin = $cronologia['terminado'] ?? null;
                $fechaCotizacion = $aceptado->copy()->subDays(2)->setTime(10, 0);
                $actualizado = $cronologia[$pedido->fresh()->estado];
                $cliente = Cliente::findOrFail($pedido->cliente_id);
                $datosCliente = $pedido->datos_cliente_acordados;
                foreach (['nombre', 'telefono', 'email', 'direccion'] as $campo) {
                    $datosCliente[$campo] = $cliente->{$campo};
                }
                DB::table('pedidos')->where('id', $pedido->id)->update([
                    'datos_cliente_acordados' => json_encode($datosCliente, JSON_UNESCAPED_UNICODE),
                    'fecha_aceptacion' => $aceptado->toDateString(),
                    'fecha_prevista_entrega' => ($cronologia['entregado'] ?? $fin ?? $corte->copy()->addDays(7 + $pedido->id % 8))->toDateString(),
                    'created_at' => $aceptado, 'updated_at' => $actualizado,
                    'materiales_validados_at' => $fin, 'precio_confirmado_at' => $fin,
                ]);
                DB::table('cotizaciones')->where('id', $pedido->cotizacion_id)->update([
                    'fecha' => $fechaCotizacion->toDateString(), 'created_at' => $fechaCotizacion, 'updated_at' => $aceptado->copy()->subDay(),
                    'validado_at' => $aceptado->copy()->subDay(), 'aprobado_at' => $aceptado->copy()->subDay(),
                ]);
                foreach ($cronologia as $estado => $fecha) {
                    DB::table('estados_pedido')->where('pedido_id', $pedido->id)->where('estado_nuevo', $estado)->update(['ocurrido_at' => $fecha, 'created_at' => $fecha, 'updated_at' => $fecha]);
                }
                $detalles = $pedido->detalles->pluck('id');
                DB::table('detalles_pedido')->whereIn('id', $detalles)->update(['created_at' => $aceptado, 'updated_at' => $fin ?? $actualizado]);
                DB::table('materiales_pedido')->whereIn('detalle_pedido_id', $detalles)->update(['created_at' => $aceptado, 'updated_at' => $fin ?? $actualizado]);
                DB::table('materiales_pedido')->whereIn('detalle_pedido_id', $detalles)->whereNotNull('cantidad_utilizada')->update(['observaciones_uso' => 'Material utilizado durante la fabricación.', 'uso_registrado_at' => $fin ?? $actualizado]);
                if ($fin) {
                    $casosIds = DB::table('casos_historicos')->whereIn('detalle_pedido_id', $detalles)->pluck('id');
                    DB::table('casos_historicos')->whereIn('id', $casosIds)->update(['fecha_referencia' => $fin->toDateString(), 'created_at' => $fin, 'updated_at' => $fin]);
                    DB::table('materiales_caso')->whereIn('caso_historico_id', $casosIds)->update(['created_at' => $fin, 'updated_at' => $fin]);
                }
                $pagos = $pedido->pagos()->orderBy('id')->get();
                foreach ($pagos as $numero => $pago) {
                    $fecha = $aceptado->copy()->addDays($numero);
                    DB::table('pagos')->where('id', $pago->id)->update(['fecha' => $fecha->toDateString(), 'created_at' => $fecha, 'updated_at' => $fecha, 'observaciones' => 'Abono del pedido.']);
                }
                if ($fin && $pagos->isEmpty()) {
                    $total = BigDecimal::of($pedido->precio_total_acordado);
                    $anticipo = $total->multipliedBy('0.4')->toScale(2, RoundingMode::HALF_UP);
                    foreach ([[$aceptado, $anticipo, 'Anticipo del pedido.'], [$cronologia['entregado'] ?? $fin, $total->minus($anticipo), 'Cancelación del saldo.']] as $numero => [$fecha, $importe, $nota]) {
                        $pago = app(PagoService::class)->guardar($actor, ['pedido_id' => $pedido->id, 'clave_registro' => (string) Str::uuid(), 'fecha' => $fecha->toDateString(), 'importe' => (string) $importe->toScale(2), 'medio' => $fecha->year < 2023 || $numero === 0 ? 'efectivo' : 'qr', 'observaciones' => $nota]);
                        DB::table('pagos')->where('id', $pago->id)->update(['created_at' => $fecha, 'updated_at' => $fecha]);
                    }
                }
            }
            $sinPedido = DB::table('cotizaciones')->whereIn('id', $cotizaciones)->whereNotIn('id', $pedidos->pluck('cotizacion_id'))->orderBy('id')->get();
            foreach ($sinPedido as $indice => $cotizacion) {
                $fecha = $corte->copy()->subDays(2 + $indice * 3)->setTime(10, 0);
                $cambios = ['fecha' => $fecha->toDateString(), 'created_at' => $fecha, 'updated_at' => $fecha];
                foreach (['validado_at', 'aprobado_at'] as $campo) {
                    if ($cotizacion->{$campo}) {
                        $cambios[$campo] = $fecha;
                    }
                }
                DB::table('cotizaciones')->where('id', $cotizacion->id)->update($cambios);
            }
            foreach (['detalles_cotizacion', 'materiales_cotizacion'] as $tabla) {
                $filas = DB::table($tabla)->when($tabla === 'detalles_cotizacion', fn ($q) => $q->whereIn('cotizacion_id', $cotizaciones), fn ($q) => $q->whereIn('detalle_cotizacion_id', DB::table('detalles_cotizacion')->whereIn('cotizacion_id', $cotizaciones)->select('id')))->get();
                foreach ($filas as $fila) {
                    $cotizacionId = $tabla === 'detalles_cotizacion' ? $fila->cotizacion_id : DB::table('detalles_cotizacion')->where('id', $fila->detalle_cotizacion_id)->value('cotizacion_id');
                    $fecha = DB::table('cotizaciones')->where('id', $cotizacionId)->value('created_at');
                    DB::table($tabla)->where('id', $fila->id)->update(['created_at' => $fecha, 'updated_at' => $fecha]);
                }
            }
            app(AuditoriaService::class)->registrar(null, $actor, 'historial_cargado', [], ['lote' => self::LOTE, 'origen' => 'simulado', 'periodo' => ['2020-01-01', self::CORTE_ANTERIOR], 'pedidos' => $pedidos->pluck('id')->all(), 'cotizaciones' => $cotizaciones, 'clientes' => $clientes->pluck('id')->all(), 'fuente' => self::class]);
        }, 5);
        $this->ordenarIds();
        $this->limitarPeriodo();
    }

    /** Reorganiza una sola vez la carga autorizada; no modifica las altas normales. */
    public function ordenarIds(string $lote = self::LOTE_ORDEN, array $cantidades = ['pedidos' => 47, 'cotizaciones' => 59, 'casos_historicos' => 87, 'pagos' => 82], bool $correlativos = false): void
    {
        DB::transaction(function () use ($lote, $cantidades, $correlativos): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'historial_ordenado')->where('datos_nuevos->lote', $lote)->exists()) {
                return;
            }
            $historial = Auditoria::where('accion', 'historial_cargado')->where('datos_nuevos->lote', self::LOTE)->sole()->datos_nuevos;
            $relaciones = [
                'cotizaciones' => [],
                'detalles_cotizacion' => ['cotizacion_id' => 'cotizaciones'],
                'materiales_cotizacion' => ['detalle_cotizacion_id' => 'detalles_cotizacion'],
                'pedidos' => ['cotizacion_id' => 'cotizaciones'],
                'detalles_pedido' => ['pedido_id' => 'pedidos', 'detalle_cotizacion_id' => 'detalles_cotizacion'],
                'materiales_pedido' => ['detalle_pedido_id' => 'detalles_pedido', 'material_cotizacion_id' => 'materiales_cotizacion'],
                'estados_pedido' => ['pedido_id' => 'pedidos'],
                'casos_historicos' => ['detalle_pedido_id' => 'detalles_pedido'],
                'materiales_caso' => ['caso_historico_id' => 'casos_historicos', 'material_pedido_id' => 'materiales_pedido'],
                'pagos' => ['pedido_id' => 'pedidos'],
            ];
            $filas = [];
            foreach ($relaciones as $tabla => $campos) {
                $filas[$tabla] = DB::table($tabla)->orderBy('id')->lockForUpdate()->get()->map(fn ($fila) => (array) $fila)->all();
            }
            foreach ($cantidades as $tabla => $cantidad) {
                if (count($filas[$tabla]) !== $cantidad) {
                    throw new \RuntimeException('La carga difiere del diagnóstico: '.$tabla);
                }
            }
            foreach (['pedidos', 'cotizaciones'] as $tabla) {
                if (array_diff(array_column($filas[$tabla], 'id'), $historial[$tabla])) {
                    throw new \RuntimeException('Hay registros ajenos al historial autorizado.');
                }
            }
            foreach ($filas['casos_historicos'] as $caso) {
                if ($caso['origen'] !== 'simulado' || $caso['estado_revision'] !== 'pendiente' || ! $caso['detalle_pedido_id']) {
                    throw new \RuntimeException('Cambió la procedencia o revisión de los casos.');
                }
            }
            // La recarga autorizada organiza los identificadores; las cargas anteriores conservan su conjunto de IDs.
            $mapas = [];
            foreach ($relaciones as $tabla => $campos) {
                $ordenadas = $filas[$tabla];
                $criterios = match ($tabla) {
                    'cotizaciones' => ['fecha'],
                    'pedidos' => ['fecha_aceptacion'],
                    'estados_pedido' => ['ocurrido_at'],
                    'casos_historicos' => ['fecha_referencia'],
                    'pagos' => ['fecha'],
                    default => array_keys($campos),
                };
                usort($ordenadas, function ($a, $b) use ($criterios, $campos, $mapas): int {
                    foreach ($criterios as $campo) {
                        $izquierda = isset($campos[$campo]) && $a[$campo] !== null ? $mapas[$campos[$campo]][$a[$campo]] : $a[$campo];
                        $derecha = isset($campos[$campo]) && $b[$campo] !== null ? $mapas[$campos[$campo]][$b[$campo]] : $b[$campo];
                        if (($comparacion = $izquierda <=> $derecha) !== 0) {
                            return $comparacion;
                        }
                    }

                    return $a['id'] <=> $b['id'];
                });
                $destinos = $correlativos ? range(1, count($filas[$tabla])) : array_column($filas[$tabla], 'id');
                foreach ($ordenadas as $indice => $fila) {
                    $mapas[$tabla][$fila['id']] = $destinos[$indice];
                }
            }
            foreach (array_reverse(array_keys($relaciones)) as $tabla) {
                DB::table($tabla)->delete();
            }
            foreach ($relaciones as $tabla => $campos) {
                $actualizadas = [];
                foreach ($filas[$tabla] as $fila) {
                    $fila['id'] = $mapas[$tabla][$fila['id']];
                    foreach ($campos as $campo => $padre) {
                        if ($fila[$campo] !== null) {
                            $fila[$campo] = $mapas[$padre][$fila[$campo]];
                        }
                    }
                    $actualizadas[] = $fila;
                }
                foreach (array_chunk($actualizadas, 100) as $bloque) {
                    DB::table($tabla)->insert($bloque);
                }
            }
            $metadatosAnteriores = [];
            // La auditoría histórica conserva sus instantáneas; solo se concilia el enlace a su registro.
            foreach (DB::table('auditorias')->orderBy('id')->get() as $auditoria) {
                $cambios = [];
                $clase = $auditoria->registro_type;
                if (is_subclass_of($clase, Model::class)) {
                    $tabla = (new $clase)->getTable();
                    if (isset($mapas[$tabla][$auditoria->registro_id])) {
                        $cambios['registro_id'] = $mapas[$tabla][$auditoria->registro_id];
                    }
                }
                $datos = json_decode($auditoria->datos_nuevos ?? 'null', true);
                $original = $datos;
                $traducir = fn ($tabla, $ids) => array_values(array_map(fn ($id) => $mapas[$tabla][$id], array_filter($ids, fn ($id) => isset($mapas[$tabla][$id]))));
                if ($auditoria->accion === 'datos_ejemplo_cargados' && is_array($datos['ids'] ?? null)) {
                    foreach ($datos['ids'] as $tabla => $ids) {
                        if (isset($mapas[$tabla])) {
                            $datos['ids'][$tabla] = $traducir($tabla, $ids);
                        }
                    }
                }
                if ($auditoria->accion === 'datos_fabricacion_cargados') {
                    $datos['ids'] = $traducir('pedidos', $datos['ids']);
                }
                if (in_array($auditoria->accion, ['datos_pagos_cargados', 'casos_pedidos_cargados', 'historial_cargado'], true)) {
                    $datos['pedidos'] = $traducir('pedidos', $datos['pedidos']);
                }
                if ($auditoria->accion === 'historial_cargado') {
                    $datos['cotizaciones'] = $traducir('cotizaciones', $datos['cotizaciones']);
                }
                if ($auditoria->accion === 'casos_pedidos_cargados') {
                    $datos['casos'] = $traducir('casos_historicos', $datos['casos']);
                    $referencias = [];
                    foreach ($datos['referencias'] as $id => $posicion) {
                        $referencias[$mapas['casos_historicos'][$id]] = $posicion;
                    }
                    $datos['referencias'] = $referencias;
                }
                if ($datos !== $original) {
                    $metadatosAnteriores[$auditoria->id] = $original;
                    $cambios['datos_nuevos'] = json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                }
                if ($cambios) {
                    DB::table('auditorias')->where('id', $auditoria->id)->update($cambios);
                }
            }
            app(AuditoriaService::class)->registrar(null, $actor, 'historial_ordenado', [], [
                'lote' => $lote, 'origen' => 'simulado', 'mapas' => $mapas,
                'metadatos_anteriores' => $metadatosAnteriores, 'fuente' => self::class,
                'criterio' => 'IDs ascendentes por fecha; listados conservan ID descendente.',
            ]);
        }, 5);
    }

    /** Finaliza una carga reconocida; no renumera altas posteriores al repetir el seeder. */
    public function finalizarIds(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        if (Auditoria::where('accion', 'contadores_historial_preparados')->where('datos_nuevos->lote', self::LOTE_CORRELATIVOS)->exists()) {
            return;
        }
        // MySQL confirma DDL de forma implícita: ajustar contadores fuera de la transacción de relaciones.
        if (DB::getDriverName() === 'mysql' && DB::transactionLevel() !== 0) {
            throw new \RuntimeException('Finaliza las transacciones antes de preparar los contadores.');
        }
        $this->ordenarIds(self::LOTE_CORRELATIVOS, ['pedidos' => 47, 'cotizaciones' => 59, 'casos_historicos' => 87, 'pagos' => 82], true);
        $tablas = ['cotizaciones', 'detalles_cotizacion', 'materiales_cotizacion', 'pedidos', 'detalles_pedido', 'materiales_pedido', 'estados_pedido', 'casos_historicos', 'materiales_caso', 'pagos'];
        foreach ($tablas as $tabla) {
            $ids = DB::table($tabla)->orderBy('id')->pluck('id')->all();
            if ($ids !== range(1, count($ids))) {
                throw new \RuntimeException('La carga no tiene identificadores correlativos: '.$tabla);
            }
            $siguiente = count($ids) + 1;
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE '.$tabla.' AUTO_INCREMENT = '.$siguiente);
            } else {
                DB::table('sqlite_sequence')->where('name', $tabla)->update(['seq' => count($ids)]);
            }
        }
        $actor = User::role('propietario')->where('activo', true)->firstOrFail();
        app(AuditoriaService::class)->registrar(null, $actor, 'contadores_historial_preparados', [], [
            'lote' => self::LOTE_CORRELATIVOS, 'casos' => 87, 'siguiente_caso' => 88,
            'criterio' => 'Carga final con identificadores consecutivos; altas nuevas mediante autoincremento.',
            'fuente' => self::class,
        ]);
    }

    /** Ajusta únicamente las fechas de la carga reconocida, nunca las altas posteriores. */
    private function limitarPeriodo(): void
    {
        DB::transaction(function (): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'historial_periodo_actualizado')->where('datos_nuevos->lote', self::LOTE_PERIODO)->exists()) {
                return;
            }
            $historial = Auditoria::where('accion', 'historial_cargado')->where('datos_nuevos->lote', self::LOTE)->sole();
            $datos = $historial->datos_nuevos;
            foreach (['pedidos' => 47, 'cotizaciones' => 59] as $tabla => $cantidad) {
                if (DB::table($tabla)->count() !== $cantidad || DB::table($tabla)->whereNotIn('id', $datos[$tabla])->exists()) {
                    throw new \RuntimeException('El contenido difiere de la carga autorizada: '.$tabla);
                }
            }
            if (DB::table('casos_historicos')->count() !== 87 || DB::table('casos_historicos')->where('origen', '<>', 'simulado')->exists() || DB::table('pagos')->count() !== 82) {
                throw new \RuntimeException('Los casos o pagos difieren de la carga autorizada.');
            }
            $campos = [
                'cotizaciones' => ['fecha', 'validado_at', 'aprobado_at'],
                'detalles_cotizacion' => [], 'materiales_cotizacion' => [],
                'pedidos' => ['fecha_aceptacion', 'fecha_prevista_entrega', 'materiales_validados_at', 'precio_confirmado_at'],
                'detalles_pedido' => [], 'materiales_pedido' => ['uso_registrado_at'],
                'estados_pedido' => ['ocurrido_at'],
                'casos_historicos' => ['fecha_referencia', 'materiales_revisados_at', 'precio_revisado_at'],
                'materiales_caso' => [], 'pagos' => ['fecha'],
            ];
            $inicio = Carbon::parse('2026-01-01')->startOfDay();
            $finAnterior = Carbon::parse(self::CORTE_ANTERIOR)->startOfDay();
            $limiteAnterior = Carbon::parse('2026-12-01')->startOfDay();
            $fin = Carbon::parse(self::CORTE)->startOfDay();
            $diasAntes = (int) $inicio->diffInDays($finAnterior);
            $diasAhora = (int) $inicio->diffInDays($fin);
            $convertir = function (string $valor) use ($inicio, $limiteAnterior, $diasAntes, $diasAhora): string {
                $fecha = Carbon::parse($valor);
                if ($fecha->lt($inicio)) {
                    return $valor;
                }
                if ($fecha->copy()->startOfDay()->gt($limiteAnterior)) {
                    throw new \RuntimeException('Fecha ajena al período diagnosticado.');
                }
                $dias = (int) $inicio->diffInDays($fecha->copy()->startOfDay());
                $nueva = $inicio->copy()->addDays(intdiv(min($dias, $diasAntes) * $diasAhora, $diasAntes));

                return strlen($valor) === 10 ? $nueva->toDateString() : $nueva->toDateString().' '.$fecha->format('H:i:s');
            };
            $anteriores = [];
            foreach ($campos as $tabla => $fechas) {
                foreach (DB::table($tabla)->orderBy('id')->lockForUpdate()->get() as $fila) {
                    $cambios = [];
                    foreach ([...$fechas, 'created_at', 'updated_at'] as $campo) {
                        if ($fila->{$campo} !== null && ($nuevo = $convertir($fila->{$campo})) !== $fila->{$campo}) {
                            $anteriores[$tabla][$fila->id][$campo] = $fila->{$campo};
                            $cambios[$campo] = $nuevo;
                        }
                    }
                    if ($cambios) {
                        DB::table($tabla)->where('id', $fila->id)->update($cambios);
                    }
                }
            }
            $datos['periodo'] = ['2020-01-01', self::CORTE];
            DB::table('auditorias')->where('id', $historial->id)->update(['datos_nuevos' => json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            app(AuditoriaService::class)->registrar(null, $actor, 'historial_periodo_actualizado', [], [
                'lote' => self::LOTE_PERIODO, 'origen' => 'simulado', 'corte' => self::CORTE,
                'periodo_anterior' => $historial->datos_nuevos['periodo'], 'fechas_anteriores' => $anteriores,
                'criterio' => 'Compresión cronológica de 2026; años anteriores, IDs y contenido conservados.',
                'fuente' => self::class,
            ]);
        }, 5);
    }

    public function redistribuirFechas(string $lote = self::LOTE_DISTRIBUCION, bool $todosEntregados = false, bool $solo87 = false): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function () use ($lote, $todosEntregados, $solo87): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'fechas_historial_distribuidas')->where('datos_nuevos->lote', $lote)->exists()) {
                return;
            }
            $cantidades = $solo87 ? ['pedidos' => 47, 'cotizaciones' => 59, 'casos_historicos' => 87, 'pagos' => 82] : ['pedidos' => ($todosEntregados ? 63 : 64), 'cotizaciones' => ($todosEntregados ? 75 : 76), 'casos_historicos' => 132, 'pagos' => ($todosEntregados ? 114 : 116)];
            foreach ($cantidades as $tabla => $cantidad) {
                if (DB::table($tabla)->count() !== $cantidad) {
                    throw new \RuntimeException('La carga difiere del historial autorizado: '.$tabla);
                }
            }
            $historial = Auditoria::where('accion', 'historial_cargado')->where('datos_nuevos->lote', self::LOTE)->sole();
            $datosHistorial = $historial->datos_nuevos;
            $pedidos = Pedido::whereIn('id', $datosHistorial['pedidos'])->with('detalles.casoHistorico', 'estados', 'pagos')->orderBy('id')->lockForUpdate()->get();
            if ($pedidos->count() !== $cantidades['pedidos'] || DB::table('casos_historicos')->where('origen', '<>', 'simulado')->exists()) {
                throw new \RuntimeException('Hay registros ajenos al historial reconocido.');
            }
            $anteriores = [];
            $actualizar = function (string $tabla, int $id, array $valores) use (&$anteriores): void {
                $fila = (array) DB::table($tabla)->where('id', $id)->firstOrFail();
                $anteriores[$tabla][$id] = array_intersect_key($fila, $valores);
                DB::table($tabla)->where('id', $id)->update($valores);
            };
            $entregados = $pedidos->where('estado', 'entregado')->groupBy(fn ($p) => $p->detalles->count())->map(fn ($g) => $g->values()->all())->all();
            $terminados = $pedidos->where('estado', 'terminado')->values();
            if ($terminados->count() !== ($todosEntregados ? 0 : 3) || $pedidos->where('estado', 'entregado')->count() !== ($todosEntregados ? $cantidades['pedidos'] : 53)) {
                throw new \RuntimeException('Los estados de los pedidos requieren revisión.');
            }
            $meses = [
                2020 => [3, 4, 5, 6, 8, 9, 10, 12],
                2021 => [1, 2, 4, 5, 7, 8, 10, 12],
                2022 => [1, 3, 4, 6, 7, 9, 10, 12],
                2023 => [1, 2, 4, 6, 7, 9, 10, 12],
                2024 => [1, 3, 4, 5, 7, 8, 10, 12],
                2025 => [1, 2, 4, 5, 7, 9, 10, 12],
                2026 => [1, 2, 3, 4, 5, 7, 8, 9],
            ];
            if ($todosEntregados) {
                $meses = [
                    2020 => [3, 4, 5, 6, 7, 8, 9, 10, 12],
                    2021 => [1, 2, 3, 4, 6, 7, 8, 10, 12],
                    2022 => [1, 2, 3, 5, 6, 7, 9, 10, 12],
                    2023 => [1, 2, 3, 4, 6, 7, 9, 10, 12],
                    2024 => [1, 2, 3, 5, 6, 7, 8, 10, 12],
                    2025 => [1, 2, 3, 4, 6, 7, 9, 10, 12],
                    2026 => [1, 2, 3, 4, 5, 6, 7, 8, 10],
                ];
            }
            if ($solo87) {
                $meses = [
                    2020 => [3, 5, 6, 8, 10, 12],
                    2021 => [1, 3, 4, 6, 8, 10, 12],
                    2022 => [1, 2, 4, 6, 8, 10, 12],
                    2023 => [1, 3, 4, 6, 8, 10, 12],
                    2024 => [1, 3, 5, 6, 8, 10, 12],
                    2025 => [1, 3, 4, 6, 8, 10, 12],
                    2026 => [1, 3, 5, 7, 8, 10],
                ];
            }
            $agenda = [];
            foreach ($meses as $ano => $listaMeses) {
                $grupos = $todosEntregados ? match ($ano) {
                    2020 => [1 => 3, 2 => 5, 3 => 1],
                    2026 => [1 => 4, 2 => 4, 4 => 1],
                    default => [1 => 3, 2 => 2, 3 => 3, 4 => 1],
                } : match ($ano) {
                    2020 => [1 => 2, 2 => 4, 3 => 2],
                    2023 => [1 => 2, 2 => 1, 3 => 4, 4 => 1],
                    2026 => [1 => 2, 2 => 2, 3 => 1],
                    default => [1 => 2, 2 => 2, 3 => 2, 4 => 2],
                };
                if ($solo87) {
                    $grupos = match ($ano) {
                        2020 => [1 => 4, 2 => 1, 4 => 1],
                        2026 => [1 => 2, 2 => 3, 4 => 1],
                        default => [1 => 3, 2 => 2, 3 => 2],
                    };
                }
                $seleccionados = [];
                while (array_sum($grupos) > 0) {
                    foreach ($grupos as $tamano => $restantes) {
                        if ($restantes > 0) {
                            $pedido = array_shift($entregados[$tamano]);
                            if (! $pedido) {
                                throw new \RuntimeException('La distribución por tamaño de pedido cambió.');
                            }
                            $seleccionados[] = $pedido;
                            $grupos[$tamano]--;
                        }
                    }
                }
                if ($ano === 2026) {
                    $seleccionados = [...$seleccionados, ...$terminados];
                }
                foreach ($seleccionados as $indice => $pedido) {
                    $fin = Carbon::create($ano, $listaMeses[$indice], [20, 23, 18, 25, 21, 24, 19, 22, 26][$indice], 16);
                    if ($todosEntregados && $ano === 2026 && $indice === ($solo87 ? 5 : 8)) {
                        $fin = Carbon::parse('2026-10-03')->setTime(16, 0);
                    }
                    $cotizacion = $fin->copy()->subDays(12 + $pedido->detalles->count() * 2)->setTime(9, 0);
                    if ($ano === 2020 && $indice === 0) {
                        $cotizacion = Carbon::parse('2020-03-16')->setTime(9, 0);
                        $fin = Carbon::parse('2020-03-30')->setTime(16, 0);
                    }
                    $aceptado = $cotizacion->copy()->addDays(2)->setTime(10, 0);
                    $agenda[$pedido->id] = ['cotizacion' => $cotizacion, 'aceptado' => $aceptado, 'en_fabricacion' => $aceptado->copy()->addDays(2), 'terminado' => $fin, 'entregado' => $fin->copy()->addDays(2)];
                }
            }
            if (array_sum(array_map('count', $entregados)) !== 0) {
                throw new \RuntimeException('Quedaron pedidos cerrados sin asignar.');
            }
            $fechasCotizacion = [];
            foreach ($agenda as $fechas) {
                $fechasCotizacion[$fechas['cotizacion']->toDateString()] = true;
            }
            $abiertos = $pedidos->whereIn('estado', ['aceptado', 'en_fabricacion'])->values();
            $fechasAbiertas = $todosEntregados ? [] : ['2026-07-06', '2026-07-20', '2026-08-03', '2026-08-17', '2026-08-31', '2026-09-09', '2026-09-18', '2026-09-28'];
            if ($abiertos->count() !== count($fechasAbiertas)) {
                throw new \RuntimeException('Cambió la cantidad de pedidos abiertos.');
            }
            $previstasAbiertas = ['2026-07-27', '2026-08-10', '2026-08-24', '2026-09-07', '2026-09-21', '2026-09-30', '2026-10-02', '2026-10-05'];
            foreach ($abiertos as $indice => $pedido) {
                $aceptado = Carbon::parse($fechasAbiertas[$indice])->setTime(10, 0);
                $cotizacion = $aceptado->copy()->subDays(3)->setTime(9, 0);
                while (isset($fechasCotizacion[$cotizacion->toDateString()])) {
                    $cotizacion->subDay();
                }
                $fechasCotizacion[$cotizacion->toDateString()] = true;
                $agenda[$pedido->id] = ['cotizacion' => $cotizacion, 'aceptado' => $aceptado, 'en_fabricacion' => $aceptado->copy()->addDays(2), 'prevista' => Carbon::parse($previstasAbiertas[$indice])->setTime(16, 0)];
            }
            $fechasPagos = [];
            foreach ($pedidos as $pedido) {
                $fechas = $agenda[$pedido->id];
                $cerrado = isset($fechas['terminado']);
                $ultima = $fechas[$pedido->estado];
                $prevista = $cerrado ? $fechas['entregado'] : $fechas['prevista'];
                $cotizacion = DB::table('cotizaciones')->where('id', $pedido->cotizacion_id)->firstOrFail();
                $aprobado = $fechas['aceptado']->copy()->subDay()->setTime(15, 0);
                $actualizar('cotizaciones', $cotizacion->id, ['fecha' => $fechas['cotizacion']->toDateString(), 'created_at' => $fechas['cotizacion'], 'updated_at' => $aprobado, 'validado_at' => $aprobado, 'aprobado_at' => $aprobado]);
                foreach (DB::table('detalles_cotizacion')->where('cotizacion_id', $cotizacion->id)->get() as $detalle) {
                    $actualizar('detalles_cotizacion', $detalle->id, ['created_at' => $fechas['cotizacion'], 'updated_at' => $aprobado]);
                    foreach (DB::table('materiales_cotizacion')->where('detalle_cotizacion_id', $detalle->id)->get() as $material) {
                        $actualizar('materiales_cotizacion', $material->id, ['created_at' => $fechas['cotizacion'], 'updated_at' => $aprobado]);
                    }
                }
                $actualizar('pedidos', $pedido->id, ['fecha_aceptacion' => $fechas['aceptado']->toDateString(), 'fecha_prevista_entrega' => $prevista->toDateString(), 'created_at' => $fechas['aceptado'], 'updated_at' => $ultima, 'materiales_validados_at' => $cerrado ? $fechas['terminado'] : null, 'precio_confirmado_at' => $cerrado ? $fechas['terminado'] : null]);
                foreach ($pedido->estados as $evento) {
                    $fecha = $fechas[$evento->estado_nuevo] ?? throw new \RuntimeException('Estado inesperado.');
                    $actualizar('estados_pedido', $evento->id, ['ocurrido_at' => $fecha, 'created_at' => $fecha, 'updated_at' => $fecha]);
                }
                foreach ($pedido->detalles as $detalle) {
                    $actualizar('detalles_pedido', $detalle->id, ['created_at' => $fechas['aceptado'], 'updated_at' => $ultima]);
                    foreach (DB::table('materiales_pedido')->where('detalle_pedido_id', $detalle->id)->get() as $material) {
                        $valores = ['created_at' => $fechas['aceptado'], 'updated_at' => $ultima];
                        if ($material->uso_registrado_at !== null) {
                            $valores['uso_registrado_at'] = $cerrado ? $fechas['terminado'] : $ultima;
                        }
                        $actualizar('materiales_pedido', $material->id, $valores);
                    }
                    if ($caso = $detalle->casoHistorico) {
                        $actualizar('casos_historicos', $caso->id, ['fecha_referencia' => $fechas['terminado']->toDateString(), 'created_at' => $fechas['terminado'], 'updated_at' => $fechas['terminado']]);
                        foreach (DB::table('materiales_caso')->where('caso_historico_id', $caso->id)->get() as $material) {
                            $actualizar('materiales_caso', $material->id, ['created_at' => $fechas['terminado'], 'updated_at' => $fechas['terminado']]);
                        }
                    }
                }
                foreach ($pedido->pagos->sortBy('fecha')->values() as $numero => $pago) {
                    $fecha = $numero === 0 ? $fechas['aceptado']->copy() : ($cerrado ? $ultima->copy() : $fechas['aceptado']->copy()->addDays($numero));
                    while (isset($fechasPagos[$fecha->toDateString()])) {
                        $fecha->addDay();
                    }
                    if ($fecha->toDateString() > self::CORTE) {
                        throw new \RuntimeException('El pago supera el corte del historial.');
                    }
                    $fechasPagos[$fecha->toDateString()] = true;
                    $actualizar('pagos', $pago->id, ['fecha' => $fecha->toDateString(), 'created_at' => $fecha, 'updated_at' => $fecha]);
                }
            }
            $fechasLibres = ['2020-06-10', '2020-11-12', '2021-03-10', '2021-09-15', '2022-02-09', '2022-08-11', '2023-03-14', '2023-11-15', '2024-02-14', '2024-09-12', '2025-06-11', '2026-06-12'];
            $sinPedido = DB::table('cotizaciones')->whereNotIn('id', $pedidos->pluck('cotizacion_id'))->orderBy('id')->get();
            if ($sinPedido->count() !== count($fechasLibres)) {
                throw new \RuntimeException('Cambiaron las cotizaciones sin pedido.');
            }
            $fechasOcupadas = array_fill_keys(DB::table('cotizaciones')->whereIn('id', $pedidos->pluck('cotizacion_id'))->pluck('fecha')->all(), true);
            foreach ($sinPedido as $indice => $cotizacion) {
                $fecha = Carbon::parse($fechasLibres[$indice])->setTime(9, 0);
                while (isset($fechasOcupadas[$fecha->toDateString()])) {
                    $fecha->addDay();
                }
                $fechasOcupadas[$fecha->toDateString()] = true;
                $valores = ['fecha' => $fecha->toDateString(), 'created_at' => $fecha, 'updated_at' => $fecha->copy()->addDays(2)];
                foreach (['validado_at', 'aprobado_at'] as $campo) {
                    if ($cotizacion->{$campo} !== null) {
                        $valores[$campo] = $fecha->copy()->addDay();
                    }
                }
                $actualizar('cotizaciones', $cotizacion->id, $valores);
                foreach (DB::table('detalles_cotizacion')->where('cotizacion_id', $cotizacion->id)->get() as $detalle) {
                    $actualizar('detalles_cotizacion', $detalle->id, ['created_at' => $fecha, 'updated_at' => $fecha->copy()->addDays(2)]);
                    foreach (DB::table('materiales_cotizacion')->where('detalle_cotizacion_id', $detalle->id)->get() as $material) {
                        $actualizar('materiales_cotizacion', $material->id, ['created_at' => $fecha, 'updated_at' => $fecha->copy()->addDays(2)]);
                    }
                }
            }
            $datosHistorial['periodo'] = ['2020-03-16', self::CORTE];
            DB::table('auditorias')->where('id', $historial->id)->update(['datos_nuevos' => json_encode($datosHistorial, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            app(AuditoriaService::class)->registrar(null, $actor, 'fechas_historial_distribuidas', ['fechas' => $anteriores, 'periodo' => $historial->datos_nuevos['periodo']], [
                'lote' => $lote, 'desde' => '2020-03-16', 'hasta' => self::CORTE,
                'casos_por_ano' => $solo87 ? [2020 => 10, 2021 => 13, 2022 => 13, 2023 => 13, 2024 => 13, 2025 => 13, 2026 => 12] : [2020 => 16, 2021 => 20, 2022 => 20, 2023 => 20, 2024 => 20, 2025 => 20, 2026 => 16],
                'criterio' => 'Un pedido cerrado por mes seleccionado; fechas únicas entre pedidos y pagos distintos.',
                'fuente' => self::class,
            ]);
            $this->ordenarIds($lote, $cantidades);
        }, 5);
    }
}
