<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\CasoHistorico;
use App\Models\Material;
use App\Models\User;
use App\Services\AuditoriaService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsumosMaterialesSeeder extends Seeder
{
    public const LOTE = 'consumos_muebles_referencia_v2';

    public const ESPECIFICACIONES = [
        'Madera' => 'Pieza de referencia: tablón de 240 × 20 × 5 cm (0,024 m³). Despiece con 20 % para corte/cepillado. Paneles anchos encolados y patas gruesas laminadas. Se asignan piezas enteras por mueble; los sobrantes quedan incluidos.',
        'Melamina' => 'Tablero de referencia de 275 × 183 cm y 18 mm. Despiece rectangular, recorte perimetral de 1 cm y corte de 4 mm; reserva mínima del 10 %. Se asignan tableros enteros por mueble y se incluyen sobrantes. Fondos y cajones del mismo tablero.',
        'Canto PVC' => 'Cinta preencolada para cantos de tablero de 18 mm. Metros de bordes visibles más 10 % de reserva; no se cantean fondos ni uniones ocultas.',
        'Juego de correderas' => 'Una unidad es un juego de dos correderas para un cajón.',
        'Vidrio para vitrina' => 'Pieza de referencia de 75 × 200 cm y 4 mm. Una pieza por paño frontal, con recorte incluido; confirmar medidas y espesor antes de fabricar.',
    ];

    /** Solo prepara antecedentes de la carga, no estima nuevas cotizaciones. */
    public function calcular(array $d): array
    {
        $w = (float) $d['largo_cm'] / 100;
        $f = (float) $d['ancho_cm'] / 100;
        $h = (float) $d['alto_cm'] / 100;
        if ($w <= 0 || $f <= 0 || $h <= 0 || ! in_array($d['material_principal_nombre'], ['Madera', 'Melamina'], true)) {
            throw new \RuntimeException('Medidas o material principal sin referencia de consumo.');
        }
        $puertas = (int) $d['puertas'];
        $cajones = (int) $d['cajones'];
        $repisas = (int) $d['divisiones'];
        $madera = $d['material_principal_nombre'] === 'Madera';
        $t = $madera ? .02 : .018;
        $piezas = [];
        $panel = function (string $nombre, float $a, float $b, float $e, int $n = 1) use (&$piezas): void {
            if ($a <= 0 || $b <= 0 || $e <= 0 || $n < 1) {
                throw new \RuntimeException('Despiece no válido: '.$nombre);
            }
            for ($i = 0; $i < $n; $i++) {
                $canto = match ($nombre) {
                    'Fondo', 'Fondo de cajón', 'Fondo de cajonera' => 0.,
                    'Tapa', 'Puertas', 'Frente exterior de cajón' => 2 * ($a + $b),
                    'Montantes de puertas con vidrio', 'Travesaños y junquillos', 'Travesaño' => 2 * $a,
                    default => $a,
                };
                $piezas[] = ['componente' => $nombre, 'largo_m' => $a, 'ancho_m' => $b, 'espesor_m' => $e, 'canto_visible_m' => $canto];
            }
        };
        $bisagras = 0;
        $vidrios = 0;
        $tipo = $d['tipo_nombre'];
        if ($tipo === 'Mesa') {
            $panel('Tapa', $w, $f, $madera ? .03 : $t);
            if ($madera) {
                $panel('Patas laminadas', $h - .03, .065, .065, 4);
                $panel('Faldones largos', $w - .15, .10, .025, 2);
                $panel('Faldones cortos', $f - .15, .10, .025, 2);
            } else {
                $panel('Laterales de apoyo', $h - $t, $f * .85, $t, 2);
                $panel('Travesaño', $w - 2 * $t, $h * .25, $t);
            }
            for ($i = 0; $i < $repisas; $i++) {
                $panel('Repisa inferior', $w * .8, $f * .8, $t);
            }
        } elseif ($tipo === 'Silla') {
            if (! $madera) {
                throw new \RuntimeException('La silla de referencia utiliza madera.');
            }
            $panel('Asiento', $w, $f, .025);
            $panel('Patas delanteras', .45, .05, .05, 2);
            $panel('Patas traseras y respaldo', $h, .05, .05, 2);
            $panel('Travesaños laterales', $f - .10, .05, .025, 4);
            $panel('Travesaños frontales', $w - .10, .05, .025, 2);
            $panel('Tablillas del respaldo', $w - .10, .08, .02, 2);
        } elseif ($tipo === 'Puerta') {
            $panel('Hoja', $w, $h, $f);
            $panel('Jambas del marco', $h, .08, .04, 2);
            $panel('Dintel del marco', $w + .16, .08, .04);
            $bisagras = max(3, (int) ceil($w * $h * $f * 600 / 15));
        } elseif ($tipo === 'Escritorio') {
            $panel('Tapa', $w, $f, $madera ? .025 : $t);
            if ($madera) {
                $panel('Patas', $h - .025, .06, .06, 4);
            } else {
                $panel('Laterales', $h - $t, $f, $t, 2);
            }
            $panel('Faldón posterior', $w - .04, $h * .3, $t);
            if ($repisas > 0) {
                $panel('Repisa', $w * .35, $f - .04, $t, $repisas);
            }
            if ($cajones + $puertas > 0) {
                $panel('Cajonera lateral', $h - .05, $f - .04, $t, 2);
                $panel('Base de cajonera', $w * .35, $f - .04, $t);
                $panel('Fondo de cajonera', $w * .35, $h - .05, $madera ? .012 : $t);
            }
        } elseif (in_array($tipo, ['Armario', 'Estante', 'Velador', 'Cómoda', 'Vitrina'], true)) {
            $panel('Laterales', $h, $f, $t, 2);
            $panel('Tapa y base', $w - 2 * $t, $f, $t, 2);
            $panel('Fondo', $w - 2 * $t, $h - 2 * $t, $madera ? .012 : $t);
            $columnas = max(1, (int) ceil($w / .8));
            if ($columnas > 1) {
                $panel('Separación vertical', $h - 2 * $t, $f - $t, $t, $columnas - 1);
            }
            if ($repisas > 0) {
                $panel('Repisas', $w / $columnas - 2 * $t, $f - $t, $t, $repisas);
            }
        } else {
            throw new \RuntimeException('Tipo sin despiece de referencia: '.$tipo);
        }
        $columnas = $tipo === 'Escritorio' ? 1 : max(1, (int) ceil($w / .8));
        if ($cajones > 0) {
            $cw = ($tipo === 'Escritorio' ? $w * .35 : $w / $columnas) - .06;
            $prof = $f - .06;
            $altura = max(.08, min(.18, $h / max(1, ceil($cajones / $columnas) + ($puertas ? 1 : 0)) - .05));
            $panel('Laterales de cajón', $prof, $altura, $madera ? .015 : $t, 2 * $cajones);
            $panel('Frente y trasera de cajón', $cw, $altura, $madera ? .015 : $t, 2 * $cajones);
            $panel('Fondo de cajón', $cw, $prof, $madera ? .012 : $t, $cajones);
            $panel('Frente exterior de cajón', $cw + .04, $altura + .03, $t, $cajones);
        }
        if ($puertas > 0) {
            $columnasPuerta = max(1, (int) ceil($w / .65));
            $filasPuerta = max(1, (int) ceil($puertas / $columnasPuerta));
            $ancho = ($tipo === 'Escritorio' ? $w * .35 : $w / min($puertas, $columnasPuerta)) - .004;
            $alto = $h * ($cajones ? .6 : 1) / $filasPuerta - .008;
            if ($tipo === 'Vitrina') {
                $panel('Montantes de puertas con vidrio', $alto, .05, $t, 2 * $puertas);
                $panel('Travesaños y junquillos', $ancho - .10, .05, $t, 2 * $puertas);
                if ($ancho - .10 > .75 || $alto - .10 > 2) {
                    throw new \RuntimeException('El paño no cabe en la pieza de vidrio.');
                }
                $vidrios = $puertas;
                $peso = ($ancho - .10) * ($alto - .10) * .004 * 2500 + 2;
            } else {
                $panel('Puertas', $ancho, $alto, $t, $puertas);
                $peso = $ancho * $alto * $t * ($madera ? 600 : 650);
            }
            $bisagras += $puertas * max(2, (int) ceil($alto / .6) + 1, (int) ceil($peso / 4));
        }
        $area = 0.;
        $volumen = 0.;
        $superficie = 0.;
        foreach ($piezas as $p) {
            $area += $p['largo_m'] * $p['ancho_m'];
            $volumen += $p['largo_m'] * $p['ancho_m'] * $p['espesor_m'];
            $superficie += 2 * ($p['largo_m'] * $p['ancho_m'] + ($p['largo_m'] + $p['ancho_m']) * $p['espesor_m']);
        }
        $materiales = [$d['material_principal_nombre'] => $madera ? (int) ceil($volumen * 1.2 / .024) : $this->tableros($piezas, $area)];
        // Montaje de referencia: madera encolada/ensamblada, melamina atornillada.
        // Mesa: ocho fijaciones de faldones/apoyos y cuatro de la tapa.
        $baseTornillos = match ($tipo) {
            'Mesa' => 12,
            'Silla' => 4,
            'Puerta' => 6,
            'Escritorio' => ($madera ? 8 : 12) + ($cajones + $puertas > 0 ? 8 : 0),
            default => $madera ? 8 : 12,
        };
        $tornillosMontaje = [
            'estructura' => $baseTornillos,
            'repisas_fijas' => in_array($tipo, ['Mesa', 'Escritorio'], true) ? $repisas * 4 : 0,
            // Por cajón: ocho para el par de correderas; cuatro en caja encolada
            // de madera u ocho en caja atornillada de melamina.
            'cajones_y_correderas' => $cajones * ($madera ? 12 : 16),
            'bisagras' => $bisagras * 4,
        ];
        $materiales[$madera ? 'Tornillo para madera' : 'Tornillo confirmat'] = array_sum($tornillosMontaje);
        if ($bisagras) {
            $materiales[$madera ? 'Bisagra' : 'Bisagra de cazoleta'] = $bisagras;
        }
        if ($cajones) {
            $materiales['Juego de correderas'] = $cajones;
        }
        if ($puertas + $cajones) {
            $materiales[$madera ? 'Pomo de madera' : 'Tirador metálico'] = $puertas + $cajones;
        }
        if ($repisas && ! in_array($tipo, ['Mesa', 'Escritorio'], true)) {
            $materiales['Soporte de repisa'] = 4 * $repisas;
        }
        if ($vidrios) {
            $materiales['Vidrio para vitrina'] = $vidrios;
        }
        $redondear = fn (float $n): string => number_format(ceil(($n - 1e-9) * 100) / 100, 2, '.', '');
        if ($madera) {
            $materiales['Cola para madera'] = $redondear(max(.03, $area * .03));
            $materiales['Lija'] = max(1, (int) ceil($superficie / 2));
        } else {
            $canto = array_sum(array_column($piezas, 'canto_visible_m'));
            $materiales['Canto PVC'] = $redondear($canto * 1.1);
        }
        if (in_array($d['acabado'], ['Barnizado natural', 'Pintado'], true)) {
            $materiales['Sellador para madera'] = $redondear($superficie * 1.1 / 10);
            $materiales[$d['acabado'] === 'Pintado' ? 'Pintura' : 'Barniz'] = $redondear($superficie * 2 * 1.1 / 9);
            $materiales['Disolvente'] = $redondear(($superficie * 1.1 / 10 + $superficie * 2 * 1.1 / 9) * .2);
        }

        return ['materiales' => $materiales, 'montaje_tornillos' => $tornillosMontaje, 'despiece' => $piezas, 'area_m2' => round($area, 4), 'volumen_m3' => round($volumen, 6), 'superficie_acabado_m2' => round($superficie, 4)];
    }

    private function tableros(array $paneles, float $area): int
    {
        usort($paneles, fn ($a, $b) => max($b['largo_m'], $b['ancho_m']) <=> max($a['largo_m'], $a['ancho_m']));
        $libres = [];
        $tableros = 0;
        foreach ($paneles as $p) {
            $eleccion = null;
            foreach ($libres as $i => [$x, $y]) {
                foreach ([[$p['largo_m'], $p['ancho_m']], [$p['ancho_m'], $p['largo_m']]] as [$a, $b]) {
                    if ($a <= $x && $b <= $y && ($eleccion === null || $x * $y < $eleccion[3])) {
                        $eleccion = [$i, $a, $b, $x * $y];
                    }
                }
            }
            if ($eleccion === null) {
                $a = max($p['largo_m'], $p['ancho_m']);
                $b = min($p['largo_m'], $p['ancho_m']);
                if ($a > 2.73 || $b > 1.81) {
                    throw new \RuntimeException('El panel no cabe en el tablero de referencia.');
                }
                $libres[] = [2.73, 1.81];
                $tableros++;
                $eleccion = [array_key_last($libres), $a, $b, 2.73 * 1.81];
            }
            [$i, $a, $b] = $eleccion;
            [$x, $y] = $libres[$i];
            unset($libres[$i]);
            if ($x - $a - .004 > 0) {
                $libres[] = [$x - $a - .004, $y];
            }
            if ($y - $b - .004 > 0) {
                $libres[] = [$a, $y - $b - .004];
            }
        }

        return max($tableros, (int) ceil($area * 1.1 / (2.75 * 1.83)));
    }

    public function run(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        $aplicado = false;
        DB::transaction(function () use (&$aplicado): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'consumos_materiales_actualizados')->where('datos_nuevos->lote', self::LOTE)->exists()) {
                return;
            }
            $casos = CasoHistorico::orderBy('id')->lockForUpdate()->get();
            if ($casos->count() !== 87 || $casos->contains(fn ($c) => $c->origen !== 'simulado' || $c->estado_revision !== 'pendiente' || ! $c->detalle_pedido_id)) {
                throw new \RuntimeException('La carga difiere de los 87 antecedentes autorizados.');
            }
            $materiales = Material::with('unidad')->get()->keyBy('nombre');
            $antes = [];
            $guardar = function (string $tabla, array $ids) use (&$antes): void {
                $antes[$tabla] = DB::table($tabla)->whereIn('id', $ids)->get()->map(fn ($f) => (array) $f)->all();
            };
            $dp = $casos->pluck('detalle_pedido_id')->all();
            $dc = DB::table('detalles_pedido')->whereIn('id', $dp)->pluck('detalle_cotizacion_id')->all();
            $pedidos = DB::table('detalles_pedido')->whereIn('id', $dp)->distinct()->pluck('pedido_id')->all();
            $cotizaciones = DB::table('detalles_cotizacion')->whereIn('id', $dc)->distinct()->pluck('cotizacion_id')->all();
            foreach (['casos_historicos' => $casos->pluck('id')->all(), 'detalles_pedido' => $dp, 'detalles_cotizacion' => $dc, 'pedidos' => $pedidos, 'cotizaciones' => $cotizaciones] as $t => $ids) {
                $guardar($t, $ids);
            }
            foreach (['materiales_caso' => ['caso_historico_id', $casos->pluck('id')->all()], 'materiales_pedido' => ['detalle_pedido_id', $dp], 'materiales_cotizacion' => ['detalle_cotizacion_id', $dc], 'pagos' => ['pedido_id', $pedidos]] as $t => [$campo, $ids]) {
                $guardar($t, DB::table($t)->whereIn($campo, $ids)->pluck('id')->all());
            }
            $guardar('materiales', $materiales->whereIn('nombre', array_keys(self::ESPECIFICACIONES))->pluck('id')->all());
            DB::table('materiales_caso')->whereIn('caso_historico_id', $casos->pluck('id'))->delete();
            DB::table('materiales_pedido')->whereIn('detalle_pedido_id', $dp)->delete();
            DB::table('materiales_cotizacion')->whereIn('detalle_cotizacion_id', $dc)->delete();
            foreach (self::ESPECIFICACIONES as $nombre => $especificaciones) {
                DB::table('materiales')->where('nombre', $nombre)->update(['especificaciones' => $especificaciones]);
            }
            $planes = [];
            foreach ($casos as $caso) {
                $detallePedido = DB::table('detalles_pedido')->find($caso->detalle_pedido_id);
                $detalle = DB::table('detalles_cotizacion')->find($detallePedido->detalle_cotizacion_id);
                $cotizacion = DB::table('cotizaciones')->find($detalle->cotizacion_id);
                $plan = $this->calcular($caso->getAttributes());
                $planes[$caso->id] = $plan;
                $suma = BigDecimal::zero();
                foreach ($plan['materiales'] as $nombre => $unitaria) {
                    $material = $materiales->get($nombre);
                    if (! $material || ! $material->activo || BigDecimal::of($material->precio_referencia)->isLessThanOrEqualTo(0)) {
                        throw new \RuntimeException('Falta material con costo conocido: '.$nombre);
                    }
                    $cantidad = BigDecimal::of((string) $unitaria)->multipliedBy($caso->cantidad_muebles);
                    $subtotal = $cantidad->multipliedBy($material->precio_referencia)->toScale(2, RoundingMode::HALF_UP);
                    $suma = $suma->plus($subtotal);
                    $comun = ['material_id' => $material->id, 'unidad_id' => $material->unidad_id, 'material_nombre' => $nombre, 'unidad_nombre' => $material->unidad->nombre, 'componente' => 'Despiece y montaje', 'created_at' => $detalle->created_at, 'updated_at' => $detalle->updated_at];
                    $posicion = array_search($nombre, array_keys($plan['materiales']), true) + 1;
                    $mc = DB::table('materiales_cotizacion')->insertGetId($comun + ['detalle_cotizacion_id' => $detalle->id, 'posicion' => $posicion, 'cantidad_unitaria' => (string) $unitaria, 'cantidad_total_nominal' => (string) $cantidad, 'cantidad_total_aplicada' => (string) $cantidad, 'precio_referencia_capturado' => $material->precio_referencia, 'costo_unitario_estimado' => $material->precio_referencia, 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo', 'subtotal_nominal' => (string) $subtotal, 'subtotal_aplicado' => (string) $subtotal]);
                    $fechasPedido = ['created_at' => DB::table('pedidos')->where('id', $detallePedido->pedido_id)->value('created_at'), 'updated_at' => $caso->updated_at->format('Y-m-d H:i:s')];
                    $mp = DB::table('materiales_pedido')->insertGetId(array_replace($comun, $fechasPedido) + ['detalle_pedido_id' => $detallePedido->id, 'material_cotizacion_id' => $mc, 'cantidad_inicial' => (string) $cantidad, 'cantidad_planificada' => (string) $cantidad, 'cantidad_utilizada' => (string) $cantidad, 'actualizado_por_id' => $actor->id, 'uso_registrado_por_id' => $actor->id, 'uso_registrado_at' => $caso->fecha_referencia->format('Y-m-d').' 16:00:00', 'observaciones_uso' => 'Consumo del despiece de referencia por mueble; incluye recortes.']);
                    DB::table('materiales_caso')->insert(array_replace($comun, ['created_at' => $caso->created_at->format('Y-m-d H:i:s'), 'updated_at' => $caso->updated_at->format('Y-m-d H:i:s')]) + ['caso_historico_id' => $caso->id, 'posicion' => $posicion, 'material_pedido_id' => $mp, 'cantidad_total' => (string) $cantidad, 'costo_unitario_estimado' => $material->precio_referencia, 'subtotal_estimado' => (string) $subtotal, 'fuente_costo' => 'cotizacion_capturada']);
                }
                $total = $suma->plus($detalle->mano_obra_total_aplicada)->plus($detalle->otros_costos_total);
                $precio = $total->multipliedBy($detalle->precio_final)->dividedBy($detalle->costo_total, 2, RoundingMode::HALF_UP);
                $sugerido = $cotizacion->recargo_porcentaje === null ? null : $total->multipliedBy(BigDecimal::of($cotizacion->recargo_porcentaje)->dividedBy('100', 6)->plus(1))->toScale(2, RoundingMode::HALF_UP);
                DB::table('detalles_cotizacion')->where('id', $detalle->id)->update(['costo_materiales_total' => (string) $suma, 'costo_total' => (string) $total, 'precio_sugerido' => $sugerido === null ? null : (string) $sugerido, 'precio_final' => (string) $precio, 'precio_propuesto' => $detalle->precio_propuesto === null ? null : (string) $total->multipliedBy($detalle->precio_propuesto)->dividedBy($detalle->costo_total, 2, RoundingMode::HALF_UP)]);
                DB::table('detalles_pedido')->where('id', $detallePedido->id)->update(['precio_acordado' => (string) $precio]);
                DB::table('casos_historicos')->where('id', $caso->id)->update(['costo_materiales_estimado_total' => (string) $suma, 'costo_estimado_total' => (string) $total, 'costo_presupuestado_total' => (string) $total, 'precio_historico_total' => (string) $precio, 'version' => $caso->version + 1]);
            }
            foreach ($cotizaciones as $id) {
                $detalles = DB::table('detalles_cotizacion')->where('cotizacion_id', $id)->get();
                $valores = [];
                foreach (['costo_materiales' => 'costo_materiales_total', 'costo_mano_obra' => 'mano_obra_total_aplicada', 'otros_costos' => 'otros_costos_total', 'costo_total' => 'costo_total', 'precio_final' => 'precio_final', 'precio_sugerido' => 'precio_sugerido'] as $campo => $origen) {
                    $v = BigDecimal::zero();
                    foreach ($detalles as $d) {
                        if ($d->{$origen} === null) {
                            $v = null;
                            break;
                        }
                        $v = $v->plus($d->{$origen});
                    }
                    $valores[$campo] = $v === null ? null : (string) $v;
                }
                DB::table('cotizaciones')->where('id', $id)->update($valores);
            }
            foreach ($antes['pedidos'] as $pedido) {
                $total = BigDecimal::of(DB::table('detalles_pedido')->where('pedido_id', $pedido['id'])->sum('precio_acordado'));
                $pagos = DB::table('pagos')->where('pedido_id', $pedido['id'])->orderBy('id')->get();
                $pagado = BigDecimal::zero();
                foreach ($pagos as $pago) {
                    $pagado = $pagado->plus($pago->importe);
                }
                $objetivo = $pagado->multipliedBy($total)->dividedBy($pedido['precio_total_acordado'], 2, RoundingMode::HALF_UP);
                $acumulado = BigDecimal::zero();
                foreach ($pagos as $i => $pago) {
                    $importe = $i === $pagos->count() - 1 ? $objetivo->minus($acumulado) : BigDecimal::of($pago->importe)->multipliedBy($total)->dividedBy($pedido['precio_total_acordado'], 2, RoundingMode::HALF_UP);
                    DB::table('pagos')->where('id', $pago->id)->update(['importe' => (string) $importe]);
                    $acumulado = $acumulado->plus($importe);
                }
                DB::table('pedidos')->where('id', $pedido['id'])->update(['precio_total_acordado' => (string) $total]);
            }
            app(AuditoriaService::class)->registrar(null, $actor, 'consumos_materiales_actualizados', ['registros' => $antes], ['lote' => self::LOTE, 'casos' => 87, 'planes' => $planes, 'especificaciones' => self::ESPECIFICACIONES, 'criterio' => 'Despieces de referencia, cantidades por unidad y totales por grupo; precios mantienen el recargo efectivo y pagos la proporción anterior. No modifica existencias.', 'fuente' => self::class]);
            app(HistorialCarpinteriaSeeder::class)->ordenarIds(self::LOTE, ['pedidos' => 47, 'cotizaciones' => 59, 'casos_historicos' => 87, 'pagos' => 82], true);
            $aplicado = true;
        }, 5);
        if ($aplicado) {
            if (DB::getDriverName() === 'mysql' && DB::transactionLevel() !== 0) {
                throw new \RuntimeException('Los contadores se preparan fuera de una transacción MySQL.');
            }
            foreach (['materiales_cotizacion', 'materiales_pedido', 'materiales_caso'] as $tabla) {
                $ultimo = (int) DB::table($tabla)->max('id');
                if (DB::getDriverName() === 'mysql') {
                    DB::statement('ALTER TABLE '.$tabla.' AUTO_INCREMENT = '.($ultimo + 1));
                } else {
                    DB::table('sqlite_sequence')->where('name', $tabla)->update(['seq' => $ultimo]);
                }
            }
        }
    }
}
