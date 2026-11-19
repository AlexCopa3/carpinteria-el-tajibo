<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\CasoHistorico;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\Pedido;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CatalogoService;
use App\Services\CierrePedidoService;
use App\Services\CotizacionService;
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CasosHistoricosSeeder extends Seeder
{
    public const LOTE = 'casos_demostracion_20261109_v1';

    public const LOTE_PEDIDOS = 'casos_desde_pedidos_87_v1';

    public const LOTE_AMPLIACION = 'casos_historicos_139_v1';

    public const CANTIDAD = 87;

    public const LOTE_FINAL = 'historial_87_casos_v1';

    private const CANTIDAD_HISTORIAL = 132;

    public const LOTE_CANTIDAD = 'casos_historicos_132_v1';

    public const LOTE_ENTREGADOS = 'historial_entregado_local_sin_correo_v1';

    private const CANTIDAD_BASE = 87;

    public const REFERENCIAS = 25;

    public function run(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $propietario = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'casos_pedidos_cargados')->where('datos_nuevos->lote', self::LOTE_PEDIDOS)->exists()) {
                return;
            }
            $documento = json_decode(file_get_contents(database_path('seeders/data/casos_historicos.json')), true, 512, JSON_THROW_ON_ERROR);
            if (($documento['lote'] ?? null) !== self::LOTE || count($documento['casos'] ?? []) !== self::REFERENCIAS) {
                throw new \RuntimeException('El catálogo debe contener las 25 referencias vigentes.');
            }
            $preparados = $this->preparar($documento['casos']);
            $anteriores = CasoHistorico::whereNull('detalle_pedido_id')->where('lote', self::LOTE)->pluck('id')->all();
            if (CasoHistorico::whereNull('detalle_pedido_id')->count() !== count($anteriores)
                || ! in_array(count($anteriores), [0, self::REFERENCIAS], true)) {
                throw new \RuntimeException('Hay casos sin pedido ajenos al catálogo esperado.');
            }
            // Los dos cierres del módulo de fabricación se conservan; el resto se añade.
            app(FabricacionSeeder::class)->run();
            if (CasoHistorico::whereNotNull('detalle_pedido_id')->count() !== 2) {
                throw new \RuntimeException('Se esperaban los dos casos de pedidos anteriores a esta carga.');
            }
            $clientesIds = Auditoria::where('accion', 'datos_ejemplo_cargados')->get()->flatMap(fn ($registro) => $registro->datos_nuevos['ids']['clientes'] ?? [])->all();
            $clientes = Cliente::whereIn('id', $clientesIds)->where('activo', true)->orderBy('id')->get();
            if ($clientes->isEmpty()) {
                throw new \RuntimeException('Carga primero los clientes del sistema.');
            }
            $maestros = User::role('maestro')->where('activo', true)->orderBy('id')->pluck('id');
            $grupos = [1 => 10, 2 => 12, 3 => 9, 4 => 6];
            $tamanos = [];
            while (array_sum($grupos) > 0) {
                foreach ($grupos as $tamano => $restantes) {
                    if ($restantes > 0) {
                        $tamanos[] = $tamano;
                        $grupos[$tamano]--;
                    }
                }
            }
            $pedidos = [];
            $cotizaciones = [];
            $referencias = [];
            $posicion = 0;
            foreach ($tamanos as $indice => $tamano) {
                $escenarios = [];
                for ($linea = 0; $linea < $tamano; $linea++) {
                    $escenarios[] = $preparados[$posicion % self::REFERENCIAS];
                    $posicion++;
                }
                $cotizacion = app(CotizacionService::class)->guardar($propietario, [
                    'clave_registro' => (string) Str::uuid(), 'fecha' => today()->toDateString(), 'recargo_porcentaje' => (string) [15, 20, 25][$indice % 3],
                    'observaciones' => 'Muebles a medida para vivienda. Coordinar acabado y entrega con el cliente.',
                    'detalles' => array_column($escenarios, 'detalle'),
                ]);
                $precios = [];
                foreach ($cotizacion->detalles as $numero => $detalle) {
                    $precios[$detalle->id] = $escenarios[$numero]['precio'];
                }
                $cotizacion = app(CotizacionService::class)->aprobar($propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => $precios]);
                $pedido = app(PedidoService::class)->crear($propietario, [
                    'cotizacion_id' => $cotizacion->id, 'cliente_id' => $clientes[$indice % $clientes->count()]->id,
                    'clave_registro' => (string) Str::uuid(), 'aceptacion' => true, 'responsable_id' => $maestros->isEmpty() ? null : $maestros[$indice % $maestros->count()],
                    'fecha_prevista_entrega' => today()->addDays(3 + $indice % 20)->toDateString(), 'observaciones' => 'Coordinar entrega en La Paz o El Alto.',
                ]);
                $pedidos[] = $pedido;
                $cotizaciones[] = $cotizacion->id;
                $referencias[$pedido->id] = $escenarios;
            }
            // Registrar la procedencia antes del cierre evita atribuir revisión humana a las semillas.
            app(AuditoriaService::class)->registrar(null, $propietario, 'datos_ejemplo_cargados', [], [
                'lote' => self::LOTE_PEDIDOS, 'origen' => 'simulado', 'fuente' => 'CasosHistoricosSeeder',
                'ids' => ['pedidos' => array_map(fn ($pedido) => $pedido->id, $pedidos), 'cotizaciones' => $cotizaciones],
            ]);
            $casosNuevos = [];
            $vinculos = [];
            $numero = 0;
            foreach ($pedidos as $indice => $pedido) {
                $pedido = app(PedidoService::class)->cambiarEstado($propietario, $pedido, ['version' => $pedido->version, 'estado' => 'en_fabricacion', 'clave_operacion' => (string) Str::uuid()]);
                app(FabricacionSeeder::class)->cerrar($propietario, $pedido, $indice % 3 === 0);
                foreach ($pedido->detalles()->orderBy('posicion')->get() as $linea => $detalle) {
                    $caso = CasoHistorico::where('detalle_pedido_id', $detalle->id)->sole();
                    $casosNuevos[] = $caso->id;
                    if ($numero < self::REFERENCIAS) {
                        $vinculos[$caso->id] = $referencias[$pedido->id][$linea]['posicion'];
                    }
                    $numero++;
                }
            }
            DB::table('materiales_caso')->whereIn('caso_historico_id', $anteriores)->delete();
            DB::table('casos_historicos')->whereIn('id', $anteriores)->delete();
            // Metadatos de las filas recién creadas, dentro de su transacción inicial.
            // No se reescriben características, costos o materiales de las instantáneas.
            foreach ($vinculos as $casoId => $posicionLote) {
                DB::table('casos_historicos')->where('id', $casoId)->whereIn('id', $casosNuevos)->update(['lote' => self::LOTE, 'posicion_lote' => $posicionLote]);
            }
            if (CasoHistorico::count() !== self::CANTIDAD_BASE || CasoHistorico::whereNull('detalle_pedido_id')->exists() || count($casosNuevos) !== 85) {
                throw new \RuntimeException('La carga no produjo 87 casos vinculados a pedidos.');
            }
            app(AuditoriaService::class)->registrar(null, $propietario, 'casos_pedidos_cargados', ['casos_sin_pedido_retirados' => $anteriores], [
                'lote' => self::LOTE_PEDIDOS, 'origen' => 'simulado', 'casos' => $casosNuevos, 'referencias' => $vinculos,
                'pedidos' => array_map(fn ($pedido) => $pedido->id, $pedidos), 'fuente' => 'CasosHistoricosSeeder',
                'alcance' => 'Carga de datos ficticios desde pedidos; no constituye revisión humana de trabajos reales.',
            ]);
        }, 5);
    }

    private function preparar(array $entradas): array
    {
        $preparados = [];
        $materiales = [];
        foreach ($entradas as $entrada) {
            $tipo = TipoMueble::where('codigo', $entrada['tipo_mueble'])->where('activo', true)->firstOrFail();
            $filas = [];
            $principal = null;
            $cantidad = $entrada['cantidad_muebles'];
            $suma = BigDecimal::zero();
            foreach ($entrada['materiales'] as $fila) {
                $nombre = $fila['material'];
                $material = $materiales[$nombre] ??= Material::with('unidad', 'categoriaMaterial')->where('nombre', $nombre)->where('activo', true)->first();
                if (! $material) {
                    throw new \RuntimeException('Falta el material: '.$nombre);
                }
                if ($material->unidad->codigo !== $fila['unidad']) {
                    throw new \RuntimeException('Unidad o categoría incompatible: '.$nombre);
                }
                $total = Decimal::cantidad($fila['cantidad_total'], $material->unidad, 'cantidad_total');
                $unitaria = $total->dividedBy($cantidad, $material->unidad->fraccionaria ? 4 : 0, RoundingMode::CEILING);
                $costo = Decimal::numero($fila['costo_unitario_estimado_bs'], 6, 'costo', true);
                $suma = $suma->plus($total->multipliedBy($costo)->toScale(2, RoundingMode::HALF_UP));
                $filas[] = ['material_id' => $material->id, 'componente' => 'General', 'cantidad_unitaria' => (string) $unitaria,
                    'cantidad_total_aplicada' => (string) $total, 'motivo_ajuste_cantidad' => 'Aprovechamiento conjunto del material en el grupo de muebles.',
                    'costo_unitario_estimado' => (string) $costo, 'estado_costo' => 'conocido', 'fuente_costo' => 'estimacion_manual'];
                if ($nombre === $entrada['material_principal']) {
                    $principal = $material;
                }
            }
            if (! $principal || ! Material::principales()->whereKey($principal->id)->exists() || ! $suma->isEqualTo($entrada['costo_materiales_estimado_total_bs'])) {
                throw new \RuntimeException('Material principal o costo de referencia incompatible.');
            }
            $mano = BigDecimal::of($entrada['mano_obra_estimada_total_bs']);
            $detalle = ['tipo_mueble_id' => $tipo->id, 'material_principal_id' => $principal->id,
                'cantidad_muebles' => $cantidad, 'acabado' => $entrada['acabado'], 'observaciones' => $entrada['descripcion'].' · '.$entrada['contexto'],
                'mano_obra_unitaria' => (string) $mano->dividedBy($cantidad, 2, RoundingMode::HALF_UP),
                'mano_obra_total_aplicada' => (string) $mano, 'motivo_ajuste_mano_obra' => 'Distribución de la mano de obra del grupo de muebles.',
                'otros_costos_total' => $entrada['otros_costos_estimados_total_bs'], 'materiales' => $filas,
            ] + array_intersect_key($entrada, array_flip(['largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones']));
            $preparados[$tipo->codigo][] = ['detalle' => $detalle, 'precio' => $entrada['precio_historico_ilustrativo_total_bs'], 'posicion' => $entrada['indice_lote']];
        }
        // Intercalar familias para que un pedido pueda reunir muebles de diferentes tipos.
        $ordenados = [];
        while (array_sum(array_map('count', $preparados)) > 0) {
            foreach ($preparados as &$grupo) {
                if ($grupo) {
                    $ordenados[] = array_shift($grupo);
                }
            }
            unset($grupo);
        }

        return $ordenados;
    }

    public function ampliar(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $propietario = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'casos_pedidos_cargados')->where('datos_nuevos->lote', self::LOTE_AMPLIACION)->exists()) {
                return;
            }
            foreach (['casos_historicos' => 87, 'pedidos' => 47, 'cotizaciones' => 59, 'pagos' => 82] as $tabla => $cantidad) {
                if (DB::table($tabla)->count() !== $cantidad) {
                    throw new \RuntimeException('La base difiere del diagnóstico autorizado: '.$tabla);
                }
            }
            $historial = Auditoria::where('accion', 'historial_cargado')->where('datos_nuevos->lote', HistorialCarpinteriaSeeder::LOTE)->sole();
            if (! Auditoria::where('accion', 'historial_periodo_actualizado')->where('datos_nuevos->lote', HistorialCarpinteriaSeeder::LOTE_PERIODO)->exists()) {
                throw new \RuntimeException('Organiza primero el historial hasta octubre.');
            }
            $categoria = Categoria::where('nombre', 'Accesorios')->where('activo', true)->firstOrFail();
            if (Material::where('nombre', 'Vidrio para vitrina')->exists()) {
                throw new \RuntimeException('El vidrio requiere conciliarse con el catálogo existente.');
            }
            app(CatalogoService::class)->guardar($propietario, [
                'nombre' => 'Vidrio para vitrina', 'categoria_id' => $categoria->id,
                'unidad_id' => UnidadMaterial::where('codigo', 'pieza')->sole()->id,
                'descripcion' => 'Paño de vidrio cortado para puerta o entrepaño de vitrina.',
                'especificaciones' => 'Precio de referencia por pieza; confirmar medidas y espesor al cotizar.',
                'precio_referencia' => '90.00', 'activo' => true,
            ]);
            $documento = json_decode(file_get_contents(database_path('seeders/data/casos_historicos_ampliacion.json')), true, 512, JSON_THROW_ON_ERROR);
            if (($documento['lote'] ?? null) !== self::LOTE_AMPLIACION || count($documento['casos'] ?? []) !== 52) {
                throw new \RuntimeException('La ampliación requiere 52 detalles de muebles.');
            }
            $preparados = $this->preparar($documento['casos']);
            $clientes = Cliente::whereIn('id', $historial->datos_nuevos['clientes'])->where('activo', true)->orderBy('id')->get();
            $maestros = User::role('maestro')->where('activo', true)->orderBy('id')->pluck('id');
            if ($clientes->isEmpty()) {
                throw new \RuntimeException('Faltan los clientes del historial.');
            }
            $grupos = [1 => 4, 2 => 6, 3 => 8, 4 => 3];
            $tamanos = [];
            while (array_sum($grupos) > 0) {
                foreach ($grupos as $tamano => $restantes) {
                    if ($restantes > 0) {
                        $tamanos[] = $tamano;
                        $grupos[$tamano]--;
                    }
                }
            }
            $pedidos = [];
            $cotizaciones = [];
            $escenarios = [];
            $posicion = 0;
            foreach ($tamanos as $indice => $tamano) {
                $grupo = array_slice($preparados, $posicion, $tamano);
                $posicion += $tamano;
                $cotizacion = app(CotizacionService::class)->guardar($propietario, [
                    'clave_registro' => (string) Str::uuid(), 'recargo_porcentaje' => (string) [15, 20, 25][$indice % 3],
                    'detalles' => array_column($grupo, 'detalle'),
                ]);
                $precios = [];
                foreach ($cotizacion->detalles as $numero => $detalle) {
                    $precios[$detalle->id] = $grupo[$numero]['precio'];
                }
                $cotizacion = app(CotizacionService::class)->aprobar($propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => $precios]);
                $pedido = app(PedidoService::class)->crear($propietario, [
                    'cotizacion_id' => $cotizacion->id, 'cliente_id' => $clientes[$indice % $clientes->count()]->id,
                    'clave_registro' => (string) Str::uuid(), 'aceptacion' => true,
                    'responsable_id' => $maestros->isEmpty() ? null : $maestros[$indice % $maestros->count()],
                    'observaciones' => 'Coordinar entrega en domicilio de La Paz o El Alto.',
                ]);
                $pedidos[] = $pedido;
                $cotizaciones[] = $cotizacion->id;
                $escenarios[$pedido->id] = $grupo;
            }
            app(AuditoriaService::class)->registrar(null, $propietario, 'datos_ejemplo_cargados', [], [
                'lote' => self::LOTE_AMPLIACION, 'origen' => 'simulado', 'fuente' => self::class,
                'ids' => ['pedidos' => array_map(fn ($p) => $p->id, $pedidos), 'cotizaciones' => $cotizaciones],
            ]);
            $casos = [];
            $referencias = [];
            $inicio = Carbon::parse('2020-03-01')->setTime(10, 0);
            $dias = (int) $inicio->diffInDays(Carbon::parse('2026-09-01')->setTime(10, 0));
            foreach ($pedidos as $indice => $pedido) {
                $pedido = app(PedidoService::class)->cambiarEstado($propietario, $pedido, ['version' => $pedido->version, 'estado' => 'en_fabricacion', 'clave_operacion' => (string) Str::uuid()]);
                app(FabricacionSeeder::class)->cerrar($propietario, $pedido, true);
                $aceptado = $inicio->copy()->addDays(intdiv($dias * $indice, count($pedidos) - 1));
                $fin = $aceptado->copy()->addDays(18);
                $entrega = $fin->copy()->addDays(3);
                foreach ($pedido->detalles()->orderBy('posicion')->get() as $linea => $detalle) {
                    $caso = CasoHistorico::where('detalle_pedido_id', $detalle->id)->sole();
                    $casos[] = $caso->id;
                    $referencia = $escenarios[$pedido->id][$linea]['posicion'];
                    if ($referencia >= 36 && $referencia <= 71) {
                        $referencias[$caso->id] = $referencia;
                        DB::table('casos_historicos')->where('id', $caso->id)->update(['lote' => self::LOTE, 'posicion_lote' => $referencia]);
                    }
                    DB::table('detalles_pedido')->where('id', $detalle->id)->update(['created_at' => $aceptado, 'updated_at' => $fin]);
                    DB::table('materiales_pedido')->where('detalle_pedido_id', $detalle->id)->update(['created_at' => $aceptado, 'updated_at' => $fin, 'uso_registrado_at' => $fin]);
                    DB::table('casos_historicos')->where('id', $caso->id)->update(['fecha_referencia' => $fin->toDateString(), 'created_at' => $fin, 'updated_at' => $fin]);
                    DB::table('materiales_caso')->where('caso_historico_id', $caso->id)->update(['created_at' => $fin, 'updated_at' => $fin]);
                }
                $fechaCotizacion = $aceptado->copy()->subDays(3);
                $fechaAprobacion = $aceptado->copy()->subDay();
                DB::table('cotizaciones')->where('id', $pedido->cotizacion_id)->update(['fecha' => $fechaCotizacion->toDateString(), 'created_at' => $fechaCotizacion, 'updated_at' => $fechaAprobacion, 'validado_at' => $fechaAprobacion, 'aprobado_at' => $fechaAprobacion]);
                $detalles = DB::table('detalles_cotizacion')->where('cotizacion_id', $pedido->cotizacion_id)->pluck('id');
                DB::table('detalles_cotizacion')->whereIn('id', $detalles)->update(['created_at' => $fechaCotizacion, 'updated_at' => $fechaAprobacion]);
                DB::table('materiales_cotizacion')->whereIn('detalle_cotizacion_id', $detalles)->update(['created_at' => $fechaCotizacion, 'updated_at' => $fechaAprobacion]);
                DB::table('pedidos')->where('id', $pedido->id)->update(['fecha_aceptacion' => $aceptado->toDateString(), 'fecha_prevista_entrega' => $entrega->toDateString(), 'created_at' => $aceptado, 'updated_at' => $entrega, 'materiales_validados_at' => $fin, 'precio_confirmado_at' => $fin]);
                foreach (['aceptado' => $aceptado, 'en_fabricacion' => $aceptado->copy()->addDays(2), 'terminado' => $fin, 'entregado' => $entrega] as $estado => $fecha) {
                    DB::table('estados_pedido')->where('pedido_id', $pedido->id)->where('estado_nuevo', $estado)->update(['ocurrido_at' => $fecha, 'created_at' => $fecha, 'updated_at' => $fecha]);
                }
                $total = BigDecimal::of($pedido->fresh()->precio_total_acordado);
                $anticipo = $total->multipliedBy('0.4')->toScale(2, RoundingMode::HALF_UP);
                foreach ([[$aceptado, $anticipo, 'Anticipo del pedido.'], [$entrega, $total->minus($anticipo), 'Cancelación del saldo.']] as $numero => [$fecha, $importe, $nota]) {
                    $pago = app(PagoService::class)->guardar($propietario, ['pedido_id' => $pedido->id, 'clave_registro' => (string) Str::uuid(), 'fecha' => $fecha->toDateString(), 'importe' => (string) $importe->toScale(2), 'medio' => $fecha->year < 2023 || $numero === 0 ? 'efectivo' : 'qr', 'observaciones' => $nota]);
                    DB::table('pagos')->where('id', $pago->id)->update(['created_at' => $fecha, 'updated_at' => $fecha]);
                }
            }
            if (CasoHistorico::count() !== 139 || count($casos) !== 52 || count($referencias) !== 36) {
                throw new \RuntimeException('La ampliación no produjo los 139 casos esperados.');
            }
            $datos = $historial->datos_nuevos;
            $datos['pedidos'] = array_merge($datos['pedidos'], array_map(fn ($p) => $p->id, $pedidos));
            $datos['cotizaciones'] = array_merge($datos['cotizaciones'], $cotizaciones);
            DB::table('auditorias')->where('id', $historial->id)->update(['datos_nuevos' => json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            app(AuditoriaService::class)->registrar(null, $propietario, 'casos_pedidos_cargados', [], [
                'lote' => self::LOTE_AMPLIACION, 'origen' => 'simulado', 'casos' => $casos, 'referencias' => $referencias,
                'pedidos' => array_map(fn ($p) => $p->id, $pedidos), 'fuente' => self::class,
                'alcance' => 'Ampliación de antecedentes; no constituye revisión humana de trabajos reales.',
            ]);
            app(HistorialCarpinteriaSeeder::class)->ordenarIds(self::LOTE_AMPLIACION, ['pedidos' => 68, 'cotizaciones' => 80, 'casos_historicos' => 139, 'pagos' => 124]);
        }, 5);
    }

    public function ajustarCantidad(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'casos_cantidad_ajustada')->where('datos_nuevos->lote', self::LOTE_CANTIDAD)->exists()) {
                return;
            }
            foreach (['casos_historicos' => 139, 'pedidos' => 68, 'cotizaciones' => 80, 'pagos' => 124] as $tabla => $cantidad) {
                if (DB::table($tabla)->count() !== $cantidad) {
                    throw new \RuntimeException('La carga difiere del diagnóstico: '.$tabla);
                }
            }
            $carga = Auditoria::where('accion', 'casos_pedidos_cargados')->where('datos_nuevos->lote', self::LOTE_AMPLIACION)->sole();
            $ids = $carga->datos_nuevos['pedidos'];
            $pedidos = [];
            $detalles = [];
            $cotizaciones = [];
            $casos = [];
            foreach ([2 => 3, 4 => 1, 5 => 2, 8 => 1] as $indice => $cantidad) {
                $pedido = Pedido::with('detalles.casoHistorico', 'pagos')->lockForUpdate()->findOrFail($ids[$indice]);
                if ($pedido->estado !== 'entregado' || $pedido->detalles->count() !== $cantidad || $pedido->pagos->count() !== 2) {
                    throw new \RuntimeException('Cambió uno de los pedidos de la ampliación.');
                }
                $pagado = BigDecimal::zero();
                foreach ($pedido->pagos as $pago) {
                    $pagado = $pagado->plus($pago->importe);
                }
                if (! $pagado->isEqualTo($pedido->precio_total_acordado)) {
                    throw new \RuntimeException('Los pagos del pedido requieren revisión.');
                }
                foreach ($pedido->detalles as $detalle) {
                    $caso = $detalle->casoHistorico;
                    if (! $caso || $caso->origen !== 'simulado' || $caso->estado_revision !== 'pendiente' || $caso->lote !== null || ! in_array($caso->id, $carga->datos_nuevos['casos'], true)) {
                        throw new \RuntimeException('No se retirarán casos ajenos ni referencias del perfil.');
                    }
                    $detalles[] = $detalle->id;
                    $casos[] = $caso->id;
                }
                $pedidos[] = $pedido->id;
                $cotizaciones[] = $pedido->cotizacion_id;
            }
            if (count($casos) !== 7) {
                throw new \RuntimeException('Se requieren exactamente siete casos para ajustar la carga.');
            }
            $detallesCotizacion = DB::table('detalles_cotizacion')->whereIn('cotizacion_id', $cotizaciones)->pluck('id')->all();
            $selecciones = [
                'materiales_caso' => DB::table('materiales_caso')->whereIn('caso_historico_id', $casos)->pluck('id')->all(),
                'casos_historicos' => $casos,
                'materiales_pedido' => DB::table('materiales_pedido')->whereIn('detalle_pedido_id', $detalles)->pluck('id')->all(),
                'estados_pedido' => DB::table('estados_pedido')->whereIn('pedido_id', $pedidos)->pluck('id')->all(),
                'pagos' => DB::table('pagos')->whereIn('pedido_id', $pedidos)->pluck('id')->all(),
                'detalles_pedido' => $detalles, 'pedidos' => $pedidos,
                'materiales_cotizacion' => DB::table('materiales_cotizacion')->whereIn('detalle_cotizacion_id', $detallesCotizacion)->pluck('id')->all(),
                'detalles_cotizacion' => $detallesCotizacion, 'cotizaciones' => $cotizaciones,
            ];
            $filas = [];
            foreach ($selecciones as $tabla => $retirar) {
                $filas[$tabla] = DB::table($tabla)->whereIn('id', $retirar)->orderBy('id')->lockForUpdate()->get()->map(fn ($f) => (array) $f)->all();
                DB::table($tabla)->whereIn('id', $retirar)->delete();
            }
            $metadatos = [];
            foreach (Auditoria::whereIn('accion', ['datos_ejemplo_cargados', 'casos_pedidos_cargados', 'historial_cargado'])->get() as $auditoria) {
                $datos = $auditoria->datos_nuevos;
                $original = $datos;
                foreach ($datos['ids'] ?? [] as $tabla => $valores) {
                    if (isset($selecciones[$tabla])) {
                        $datos['ids'][$tabla] = array_values(array_diff($valores, $selecciones[$tabla]));
                    }
                }
                foreach (['pedidos', 'cotizaciones', 'casos' => 'casos_historicos'] as $campo => $tabla) {
                    $campo = is_int($campo) ? $tabla : $campo;
                    if (isset($datos[$campo])) {
                        $datos[$campo] = array_values(array_diff($datos[$campo], $selecciones[$tabla]));
                    }
                }
                if ($datos !== $original) {
                    $metadatos[$auditoria->id] = $original;
                    DB::table('auditorias')->where('id', $auditoria->id)->update(['datos_nuevos' => json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
                }
            }
            if (CasoHistorico::count() !== self::CANTIDAD_HISTORIAL) {
                throw new \RuntimeException('El resultado debe ser de 132 casos.');
            }
            app(AuditoriaService::class)->registrar(null, $actor, 'casos_cantidad_ajustada', ['filas' => $filas, 'metadatos' => $metadatos], [
                'lote' => self::LOTE_CANTIDAD, 'cantidad' => self::CANTIDAD_HISTORIAL, 'retirados' => $selecciones,
                'criterio' => 'Retirar siete detalles sin referencia del perfil y sus cuatro pedidos completos de la ampliación.',
                'fuente' => self::class,
            ]);
        }, 5);
    }

    public function limitarA87(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'casos_cantidad_final_ajustada')->where('datos_nuevos->lote', self::LOTE_FINAL)->exists()) {
                return;
            }
            foreach (['casos_historicos' => 132, 'pedidos' => 63, 'cotizaciones' => 75, 'pagos' => 114] as $tabla => $cantidad) {
                if (DB::table($tabla)->count() !== $cantidad) {
                    throw new \RuntimeException('La carga difiere del diagnóstico: '.$tabla);
                }
            }
            $carga = Auditoria::where('accion', 'historial_cargado')->where('datos_nuevos->lote', HistorialCarpinteriaSeeder::LOTE)->sole();
            $reconocidos = $carga->datos_nuevos['pedidos'];
            $candidatos = Pedido::with('detalles.casoHistorico', 'pagos')->whereIn('id', $reconocidos)->orderBy('id')->lockForUpdate()->get()->filter(function ($pedido) {
                if ($pedido->estado !== 'entregado' || $pedido->pagos->count() !== 2) {
                    return false;
                }
                $pagado = BigDecimal::zero();
                foreach ($pedido->pagos as $pago) {
                    $pagado = $pagado->plus($pago->importe);
                }

                return $pagado->isEqualTo($pedido->precio_total_acordado) && $pedido->detalles->isNotEmpty() && $pedido->detalles->every(function ($detalle) {
                    $caso = $detalle->casoHistorico;

                    return $caso && $caso->origen === 'simulado' && $caso->estado_revision === 'pendiente' && $caso->lote === null && $caso->posicion_lote === null;
                });
            })->groupBy(fn ($pedido) => $pedido->detalles->count());
            $pedidos = [];
            $detalles = [];
            $cotizaciones = [];
            $casos = [];
            foreach ([4 => 4, 3 => 6, 2 => 5, 1 => 1] as $tamano => $cantidad) {
                $seleccion = $candidatos->get($tamano, collect())->take($cantidad);
                if ($seleccion->count() !== $cantidad) {
                    throw new \RuntimeException('No hay suficientes pedidos completos sin referencias para ajustar la carga.');
                }
                foreach ($seleccion as $pedido) {
                    foreach ($pedido->detalles as $detalle) {
                        $detalles[] = $detalle->id;
                        $casos[] = $detalle->casoHistorico->id;
                    }
                    $pedidos[] = $pedido->id;
                    $cotizaciones[] = $pedido->cotizacion_id;
                }
            }
            if (count($casos) !== 45 || count($pedidos) !== 16) {
                throw new \RuntimeException('El ajuste debe retirar 45 casos de 16 pedidos completos.');
            }
            $detallesCotizacion = DB::table('detalles_cotizacion')->whereIn('cotizacion_id', $cotizaciones)->pluck('id')->all();
            $selecciones = [
                'materiales_caso' => DB::table('materiales_caso')->whereIn('caso_historico_id', $casos)->pluck('id')->all(),
                'casos_historicos' => $casos,
                'materiales_pedido' => DB::table('materiales_pedido')->whereIn('detalle_pedido_id', $detalles)->pluck('id')->all(),
                'estados_pedido' => DB::table('estados_pedido')->whereIn('pedido_id', $pedidos)->pluck('id')->all(),
                'pagos' => DB::table('pagos')->whereIn('pedido_id', $pedidos)->pluck('id')->all(),
                'detalles_pedido' => $detalles, 'pedidos' => $pedidos,
                'materiales_cotizacion' => DB::table('materiales_cotizacion')->whereIn('detalle_cotizacion_id', $detallesCotizacion)->pluck('id')->all(),
                'detalles_cotizacion' => $detallesCotizacion, 'cotizaciones' => $cotizaciones,
            ];
            $filas = [];
            foreach ($selecciones as $tabla => $retirar) {
                $filas[$tabla] = DB::table($tabla)->whereIn('id', $retirar)->orderBy('id')->lockForUpdate()->get()->map(fn ($f) => (array) $f)->all();
                DB::table($tabla)->whereIn('id', $retirar)->delete();
            }
            $metadatos = [];
            foreach (Auditoria::whereIn('accion', ['datos_ejemplo_cargados', 'casos_pedidos_cargados', 'historial_cargado'])->get() as $auditoria) {
                $datos = $auditoria->datos_nuevos;
                $original = $datos;
                foreach ($datos['ids'] ?? [] as $tabla => $valores) {
                    if (isset($selecciones[$tabla])) {
                        $datos['ids'][$tabla] = array_values(array_diff($valores, $selecciones[$tabla]));
                    }
                }
                foreach (['pedidos', 'cotizaciones', 'casos' => 'casos_historicos'] as $campo => $tabla) {
                    $campo = is_int($campo) ? $tabla : $campo;
                    if (isset($datos[$campo])) {
                        $datos[$campo] = array_values(array_diff($datos[$campo], $selecciones[$tabla]));
                    }
                }
                if ($datos !== $original) {
                    $metadatos[$auditoria->id] = $original;
                    DB::table('auditorias')->where('id', $auditoria->id)->update(['datos_nuevos' => json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
                }
            }
            if (CasoHistorico::count() !== self::CANTIDAD) {
                throw new \RuntimeException('El resultado debe ser de 87 casos.');
            }
            app(HistorialCarpinteriaSeeder::class)->redistribuirFechas(self::LOTE_FINAL, true, true);
            app(AuditoriaService::class)->registrar(null, $actor, 'casos_cantidad_final_ajustada', ['filas' => $filas, 'metadatos' => $metadatos], [
                'lote' => self::LOTE_FINAL, 'cantidad' => self::CANTIDAD, 'retirados' => $selecciones,
                'criterio' => 'Retirar 45 casos sin referencias del perfil con sus 16 pedidos completos; conservar los 61 descriptores del recomendador.',
                'fuente' => self::class,
            ]);
        }, 5);
    }

    public function entregarHistorial(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $actor = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'historial_finalizado')->where('datos_nuevos->lote', self::LOTE_ENTREGADOS)->exists()) {
                return;
            }
            foreach (['pedidos' => 64, 'cotizaciones' => 76, 'casos_historicos' => 132, 'pagos' => 116] as $tabla => $cantidad) {
                if (DB::table($tabla)->count() !== $cantidad) {
                    throw new \RuntimeException('La carga difiere del diagnóstico: '.$tabla);
                }
            }
            $carga = Auditoria::where('accion', 'casos_pedidos_cargados')->where('datos_nuevos->lote', self::LOTE_AMPLIACION)->sole();
            $retirar = CasoHistorico::whereIn('id', $carga->datos_nuevos['casos'])->whereNull('posicion_lote')->with('detallePedido')->orderBy('id')->lockForUpdate()->get();
            if ($retirar->count() !== 9 || $retirar->contains(fn ($c) => $c->lote !== null || $c->origen !== 'simulado' || $c->estado_revision !== 'pendiente')) {
                throw new \RuntimeException('Solo se retirarán los nueve antecedentes adicionales sin referencia del perfil.');
            }
            $antes = [];
            $eliminados = [];
            $guardar = function (string $tabla, int $id, array $valores) use (&$antes): void {
                $fila = (array) DB::table($tabla)->where('id', $id)->firstOrFail();
                $antes[$tabla][$id] ??= $fila;
                DB::table($tabla)->where('id', $id)->update($valores);
            };
            $borrar = function (string $tabla, array $ids) use (&$eliminados): void {
                $eliminados[$tabla] = array_merge($eliminados[$tabla] ?? [], DB::table($tabla)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->map(fn ($f) => (array) $f)->all());
                DB::table($tabla)->whereIn('id', $ids)->delete();
            };
            $pedidosAfectados = $retirar->pluck('detallePedido.pedido_id')->unique()->values();
            $detalles = $retirar->pluck('detalle_pedido_id')->all();
            $detallesCotizacion = DB::table('detalles_pedido')->whereIn('id', $detalles)->pluck('detalle_cotizacion_id')->all();
            $borrar('materiales_caso', DB::table('materiales_caso')->whereIn('caso_historico_id', $retirar->pluck('id'))->pluck('id')->all());
            $borrar('casos_historicos', $retirar->pluck('id')->all());
            $borrar('materiales_pedido', DB::table('materiales_pedido')->whereIn('detalle_pedido_id', $detalles)->pluck('id')->all());
            $borrar('detalles_pedido', $detalles);
            $borrar('materiales_cotizacion', DB::table('materiales_cotizacion')->whereIn('detalle_cotizacion_id', $detallesCotizacion)->pluck('id')->all());
            $borrar('detalles_cotizacion', $detallesCotizacion);
            foreach ($pedidosAfectados as $pedidoId) {
                $pedido = Pedido::with('detalles', 'pagos')->lockForUpdate()->findOrFail($pedidoId);
                if ($pedido->estado !== 'entregado' || $pedido->pagos->count() !== 2) {
                    throw new \RuntimeException('Uno de los pedidos adicionales cambió.');
                }
                $pagado = BigDecimal::zero();
                foreach ($pedido->pagos as $pago) {
                    $pagado = $pagado->plus($pago->importe);
                }
                if (! $pagado->isEqualTo($pedido->precio_total_acordado)) {
                    throw new \RuntimeException('Los pagos requieren conciliación previa.');
                }
                if ($pedido->detalles->isEmpty()) {
                    $borrar('pagos', $pedido->pagos->pluck('id')->all());
                    $borrar('estados_pedido', DB::table('estados_pedido')->where('pedido_id', $pedido->id)->pluck('id')->all());
                    $borrar('pedidos', [$pedido->id]);
                    $borrar('cotizaciones', [$pedido->cotizacion_id]);

                    continue;
                }
                $cotizacion = DB::table('cotizaciones')->where('id', $pedido->cotizacion_id)->firstOrFail();
                $totales = [];
                foreach (['costo_materiales' => 'costo_materiales_total', 'costo_mano_obra' => 'mano_obra_total_aplicada', 'otros_costos' => 'otros_costos_total', 'costo_total' => 'costo_total', 'precio_sugerido' => 'precio_sugerido', 'precio_final' => 'precio_final'] as $destino => $campo) {
                    $suma = BigDecimal::zero();
                    foreach (DB::table('detalles_cotizacion')->where('cotizacion_id', $cotizacion->id)->get() as $fila) {
                        if ($fila->{$campo} === null) {
                            $suma = null;
                            break;
                        }
                        $suma = $suma->plus($fila->{$campo});
                    }
                    $totales[$destino] = $suma === null ? null : (string) $suma->toScale(2, RoundingMode::HALF_UP);
                }
                $totales['version'] = $cotizacion->version + 1;
                $guardar('cotizaciones', $cotizacion->id, $totales);
                $guardar('pedidos', $pedido->id, ['precio_total_acordado' => $totales['precio_final'], 'cotizacion_aceptada_version' => $totales['version'], 'version' => $pedido->version + 1]);
                foreach ($pedido->detalles->sortBy('posicion')->values() as $indice => $detalle) {
                    $guardar('detalles_pedido', $detalle->id, ['posicion' => $indice + 1]);
                    $guardar('detalles_cotizacion', $detalle->detalle_cotizacion_id, ['posicion' => $indice + 1]);
                }
                $total = BigDecimal::of($totales['precio_final']);
                $anticipo = $total->multipliedBy('0.4')->toScale(2, RoundingMode::HALF_UP);
                foreach ($pedido->pagos->sortBy('fecha')->values() as $indice => $pago) {
                    $importe = $indice === 0 ? $anticipo : $total->minus($anticipo);
                    $guardar('pagos', $pago->id, ['importe' => (string) $importe->toScale(2), 'version' => $pago->version + 1]);
                }
            }
            foreach (Auditoria::whereIn('accion', ['datos_ejemplo_cargados', 'casos_pedidos_cargados', 'historial_cargado'])->get() as $auditoria) {
                $datos = $auditoria->datos_nuevos;
                $original = $datos;
                foreach ($datos['ids'] ?? [] as $tabla => $ids) {
                    if (isset($eliminados[$tabla])) {
                        $datos['ids'][$tabla] = array_values(array_diff($ids, array_column($eliminados[$tabla], 'id')));
                    }
                }
                foreach (['pedidos', 'cotizaciones', 'casos' => 'casos_historicos'] as $campo => $tabla) {
                    $campo = is_int($campo) ? $tabla : $campo;
                    if (isset($datos[$campo], $eliminados[$tabla])) {
                        $datos[$campo] = array_values(array_diff($datos[$campo], array_column($eliminados[$tabla], 'id')));
                    }
                }
                if ($datos !== $original) {
                    $guardar('auditorias', $auditoria->id, ['datos_nuevos' => json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
                }
            }
            $nuevosDetalles = [];
            foreach (Pedido::with('detalles')->whereIn('estado', ['aceptado', 'en_fabricacion', 'terminado'])->orderBy('id')->lockForUpdate()->get() as $pedido) {
                $antes['cierre_pedidos'][$pedido->id] = (array) DB::table('pedidos')->find($pedido->id);
                if ($pedido->estado === 'aceptado') {
                    $pedido = app(PedidoService::class)->cambiarEstado($actor, $pedido, ['version' => $pedido->version, 'estado' => 'en_fabricacion', 'clave_operacion' => (string) Str::uuid()]);
                }
                if ($pedido->estado === 'en_fabricacion') {
                    $nuevosDetalles = [...$nuevosDetalles, ...$pedido->detalles()->pluck('id')->all()];
                    app(FabricacionSeeder::class)->cerrar($actor, $pedido, true);
                } else {
                    app(CierrePedidoService::class)->entregar($actor, $pedido, ['version' => $pedido->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true]);
                }
            }
            if (Pedido::count() !== 63 || Pedido::where('estado', '<>', 'entregado')->exists() || CasoHistorico::count() !== self::CANTIDAD_HISTORIAL || count($nuevosDetalles) !== 9) {
                throw new \RuntimeException('El cierre debe producir 63 pedidos entregados y 132 casos.');
            }
            $historial = Auditoria::where('accion', 'historial_cargado')->where('datos_nuevos->lote', HistorialCarpinteriaSeeder::LOTE)->sole();
            $clientes = Cliente::whereIn('id', $historial->datos_nuevos['clientes'])->orderBy('id')->get();
            $zonas = ['La Paz · Miraflores', 'El Alto · Ciudad Satélite', 'La Paz · Villa Fátima', 'El Alto · Villa Adela', 'La Paz · San Pedro', 'El Alto · Río Seco', 'La Paz · Sopocachi', 'El Alto · Villa Dolores'];
            foreach ($clientes as $indice => $cliente) {
                $direccion = str_starts_with($cliente->direccion ?? '', 'La Paz · ') || str_starts_with($cliente->direccion ?? '', 'El Alto · ') ? $cliente->direccion : $zonas[$indice % count($zonas)];
                $guardar('clientes', $cliente->id, ['email' => null, 'direccion' => $direccion]);
            }
            foreach (Pedido::with('cliente', 'detalles.casoHistorico')->get() as $pedido) {
                $datos = $pedido->datos_cliente_acordados;
                $datos['email'] = null;
                $datos['direccion'] = $pedido->cliente->direccion;
                $guardar('pedidos', $pedido->id, ['datos_cliente_acordados' => json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
                foreach ($pedido->detalles as $detalle) {
                    $guardar('casos_historicos', $detalle->casoHistorico->id, ['contexto' => $pedido->cliente->direccion]);
                }
            }
            foreach (DB::table('cotizaciones')->whereNotNull('datos_cliente_aprobados')->get() as $cotizacion) {
                $datos = json_decode($cotizacion->datos_cliente_aprobados, true, 512, JSON_THROW_ON_ERROR);
                $datos['email'] = null;
                if (isset($datos['direccion']) && ! str_starts_with($datos['direccion'], 'La Paz · ') && ! str_starts_with($datos['direccion'], 'El Alto · ')) {
                    $datos['direccion'] = $zonas[$cotizacion->id % count($zonas)];
                }
                $guardar('cotizaciones', $cotizacion->id, ['datos_cliente_aprobados' => json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            }
            app(HistorialCarpinteriaSeeder::class)->redistribuirFechas(self::LOTE_ENTREGADOS, true);
            app(AuditoriaService::class)->registrar(null, $actor, 'historial_finalizado', ['registros_modificados' => $antes, 'registros_retirados' => $eliminados], [
                'lote' => self::LOTE_ENTREGADOS, 'casos' => self::CANTIDAD_HISTORIAL, 'pedidos_entregados' => 63,
                'ubicaciones' => ['La Paz', 'El Alto'], 'correos_clientes' => null,
                'criterio' => 'Cierre por servicios del sistema; nueve antecedentes adicionales sustituidos sin retirar referencias del perfil.',
                'fuente' => self::class,
            ]);
        }, 5);
    }
}
