<?php

namespace Tests\Feature;

use App\Models\CasoHistorico;
use App\Models\TipoMueble;
use App\Models\User;
use Database\Seeders\CasosHistoricosSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstalacionSeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    public function test_migrate_seed_pide_propietario_y_carga_ejemplos_sin_duplicar_al_repetir(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $password = 'Clave-Aislada-2026!';
        $this->artisan('migrate', ['--seed' => true])
            ->expectsQuestion('Nombre del propietario', 'Propietario de prueba')
            ->expectsQuestion('Correo electrónico', 'propietario@example.test')
            ->expectsQuestion('Contraseña (12 caracteres, mayúscula, minúscula, número y símbolo)', $password)
            ->expectsQuestion('Repite la contraseña', $password)
            ->assertSuccessful();

        $usuario = User::firstOrFail();
        $this->assertTrue($usuario->hasRole('propietario'));
        $this->assertTrue(Hash::check($password, $usuario->password));
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('personal', 2);
        $this->assertSame(2, User::where('password_temporal', true)->count());
        $this->assertDatabaseCount('clientes', 36);
        $this->assertDatabaseCount('materiales', 27);
        $this->assertFalse(DB::table('materiales')->where('nombre', 'MDF')->exists());
        $this->assertFalse(DB::table('casos_historicos')->where('material_principal_nombre', 'MDF')->exists());
        $this->assertDatabaseCount('cotizaciones', 59);
        $this->assertDatabaseCount('pedidos', 47);
        $this->assertDatabaseCount('casos_historicos', 87);
        foreach (['cotizaciones', 'detalles_cotizacion', 'materiales_cotizacion', 'pedidos', 'detalles_pedido', 'materiales_pedido', 'estados_pedido', 'casos_historicos', 'materiales_caso', 'pagos'] as $tabla) {
            $this->assertSame(range(1, DB::table($tabla)->count()), DB::table($tabla)->orderBy('id')->pluck('id')->all());
            $this->assertSame(DB::table($tabla)->count(), (int) DB::table('sqlite_sequence')->where('name', $tabla)->value('seq'));
        }

        $this->assertFalse(DB::table('casos_historicos')->whereNull('detalle_pedido_id')->exists());
        $this->assertSame(61, DB::table('casos_historicos')->where('lote', CasosHistoricosSeeder::LOTE)->count());
        foreach (DB::table('casos_historicos')->get() as $caso) {
            $materiales = DB::table('materiales_caso')->where('caso_historico_id', $caso->id)->get()->keyBy('material_nombre');
            $this->assertNotEmpty($materiales);
            if ($caso->acabado === 'Barnizado natural') {
                foreach (['Barniz', 'Sellador para madera', 'Lija'] as $nombre) {
                    $this->assertGreaterThan(0, (float) $materiales[$nombre]->cantidad_total);
                }
            }
            if ($caso->acabado === 'Sin acabado' || $caso->acabado === 'Melamina') {
                $this->assertFalse($materiales->has('Barniz'));
            }
            $this->assertEquals($caso->cajones * $caso->cantidad_muebles, $materiales->get('Juego de correderas')?->cantidad_total ?? 0);
            $this->assertEquals($caso->precio_historico_total, DB::table('detalles_pedido')->where('id', $caso->detalle_pedido_id)->value('precio_acordado'));
        }
        $this->assertDatabaseCount('pagos', 82);
        $this->assertSame(1, DB::table('cotizaciones')->where('estado', 'rechazada')->count());
        $this->assertDatabaseCount('ajustes', 1);
        $this->assertSame(0, DB::table('pedidos')->where('estado', 'terminado')->count());
        $this->assertSame(47, DB::table('pedidos')->where('estado', 'entregado')->count());
        $this->assertFalse(DB::table('cotizaciones')->whereNotNull('condiciones')->exists());
        $this->assertFalse(DB::table('cotizaciones')->whereNotNull('cliente_id')->exists());
        $this->assertGreaterThan(0, DB::table('materiales_pedido')->whereNotNull('cantidad_utilizada')->count());
        $this->assertSame('2020', substr(DB::table('casos_historicos')->min('fecha_referencia'), 0, 4));
        $this->assertSame('2026', substr(DB::table('casos_historicos')->max('fecha_referencia'), 0, 4));
        foreach (DB::table('pedidos')->get() as $pedido) {
            $cotizacion = DB::table('cotizaciones')->find($pedido->cotizacion_id);
            $this->assertLessThanOrEqual($pedido->fecha_aceptacion, $cotizacion->fecha);
            $this->assertEqualsWithDelta((float) $pedido->precio_total_acordado, (float) DB::table('detalles_pedido')->where('pedido_id', $pedido->id)->sum('precio_acordado'), 0.001);
            $pagado = (float) DB::table('pagos')->where('pedido_id', $pedido->id)->sum('importe');
            $this->assertLessThanOrEqual((float) $pedido->precio_total_acordado + 0.001, $pagado);
            foreach (DB::table('pagos')->where('pedido_id', $pedido->id)->get() as $pago) {
                $this->assertGreaterThanOrEqual($pedido->fecha_aceptacion, $pago->fecha);
                $this->assertLessThanOrEqual('2026-10-05', substr($pago->fecha, 0, 10));
            }
        }
        $this->assertSame(87, DB::table('casos_historicos')->where('origen', 'simulado')->where('estado_revision', 'pendiente')->count());
        $this->assertFalse(DB::table('materiales_pedido')->where('observaciones_uso', 'like', '%ejemplo%')->exists());
        foreach (['cotizaciones' => 'fecha', 'pedidos' => 'fecha_aceptacion', 'casos_historicos' => 'fecha_referencia', 'pagos' => 'fecha'] as $tabla => $campo) {
            $fechas = DB::table($tabla)->orderBy('id')->pluck($campo)->all();
            $ordenadas = $fechas;
            sort($ordenadas);
            $this->assertSame($ordenadas, $fechas, $tabla.' debe avanzar por fecha con sus IDs');
        }
        $this->assertStringStartsWith('2020', DB::table('pedidos')->orderBy('id')->value('fecha_aceptacion'));
        $this->assertStringStartsWith('2026', DB::table('pedidos')->orderByDesc('id')->value('fecha_aceptacion'));
        foreach (DB::table('auditorias')->where('accion', 'datos_ejemplo_cargados')->get() as $carga) {
            foreach (json_decode($carga->datos_nuevos, true)['ids'] as $tabla => $ids) {
                if (in_array($tabla, ['cotizaciones', 'pedidos', 'detalles_cotizacion', 'materiales_cotizacion', 'detalles_pedido', 'materiales_pedido', 'estados_pedido'], true)) {
                    $this->assertSame(count($ids), DB::table($tabla)->whereIn('id', $ids)->count(), 'Metadatos de '.$tabla);
                }
            }
        }
        $this->assertSame(1, DB::table('auditorias')->where('accion', 'historial_ordenado')->where('datos_nuevos->lote', CasosHistoricosSeeder::LOTE_AMPLIACION)->count());
        $referencias = [];
        foreach (DB::table('auditorias')->where('accion', 'casos_pedidos_cargados')->get() as $carga) {
            foreach (json_decode($carga->datos_nuevos, true)['referencias'] as $id => $posicion) {
                $referencias[$id] = $posicion;
            }
        }
        $this->assertCount(61, $referencias);
        foreach ($referencias as $id => $posicion) {
            $caso = DB::table('casos_historicos')->find($id);
            $this->assertSame($posicion, $caso->posicion_lote);
            $this->assertSame(CasosHistoricosSeeder::LOTE, $caso->lote);
        }
        foreach (['cotizaciones' => ['fecha', 'created_at', 'updated_at', 'validado_at', 'aprobado_at'], 'pedidos' => ['fecha_aceptacion', 'fecha_prevista_entrega', 'materiales_validados_at', 'precio_confirmado_at', 'created_at', 'updated_at'], 'casos_historicos' => ['fecha_referencia', 'created_at', 'updated_at'], 'pagos' => ['fecha', 'created_at', 'updated_at'], 'estados_pedido' => ['ocurrido_at', 'created_at', 'updated_at'], 'materiales_pedido' => ['uso_registrado_at', 'created_at', 'updated_at']] as $tabla => $campos) {
            foreach ($campos as $campo) {
                $this->assertFalse(DB::table($tabla)->where($campo, '>', '2026-10-05 23:59:59')->exists(), $tabla.'.'.$campo.' supera el corte');
            }
        }
        $this->assertSame('2026-10-03', DB::table('casos_historicos')->max('fecha_referencia'));
        foreach (['velador', 'comoda', 'vitrina'] as $codigo) {
            $tipo = TipoMueble::where('codigo', $codigo)->sole();
            $this->assertSame(12, DB::table('casos_historicos')->where('tipo_mueble_id', $tipo->id)->count());
        }
        $grupos = DB::table('casos_historicos')->join('detalles_pedido', 'detalles_pedido.id', '=', 'casos_historicos.detalle_pedido_id')->selectRaw('detalles_pedido.pedido_id, COUNT(*) cantidad')->groupBy('detalles_pedido.pedido_id')->get();
        $distribucion = array_count_values($grupos->pluck('cantidad')->map(fn ($n) => (int) $n)->all());
        ksort($distribucion);
        $this->assertSame([1 => 21, 2 => 14, 3 => 10, 4 => 2], $distribucion);
        $this->assertSame('2020-03-16', DB::table('cotizaciones')->min('fecha'));
        foreach (['cotizaciones' => 'fecha', 'pedidos' => 'fecha_aceptacion', 'pagos' => 'fecha'] as $tabla => $campo) {
            $this->assertSame(DB::table($tabla)->count(), DB::table($tabla)->distinct()->count($campo), $tabla.' no debe repetir fechas entre registros distintos');
            $this->assertFalse(DB::table($tabla)->where($campo, '<', '2020-03-16')->exists());
        }
        $this->assertSame(47, DB::table('pedidos')->distinct()->count('fecha_prevista_entrega'));
        $casosPorAno = DB::table('casos_historicos')->selectRaw('substr(fecha_referencia, 1, 4) ano, COUNT(*) cantidad')->groupByRaw('substr(fecha_referencia, 1, 4)')->orderBy('ano')->pluck('cantidad', 'ano')->map(fn ($n) => (int) $n)->all();
        $this->assertSame([2020 => 10, 2021 => 13, 2022 => 13, 2023 => 13, 2024 => 13, 2025 => 13, 2026 => 12], $casosPorAno);
        $porMes = DB::table('casos_historicos')->selectRaw('substr(fecha_referencia, 1, 7) mes, COUNT(*) cantidad')->groupByRaw('substr(fecha_referencia, 1, 7)')->get();
        $this->assertLessThanOrEqual(4, (int) $porMes->max('cantidad'));
        $pedidosPorMes = DB::table('pedidos')->selectRaw('substr(fecha_aceptacion, 1, 7) mes, COUNT(*) cantidad')->groupByRaw('substr(fecha_aceptacion, 1, 7)')->get();
        $this->assertLessThanOrEqual(4, (int) $pedidosPorMes->max('cantidad'));
        $this->assertSame(47, DB::table('casos_historicos')->distinct()->count('fecha_referencia'));
        foreach (DB::table('pedidos')->get() as $pedido) {
            $eventos = DB::table('estados_pedido')->where('pedido_id', $pedido->id)->orderBy('id')->get();
            $ultima = null;
            foreach ($eventos as $evento) {
                $this->assertGreaterThanOrEqual($pedido->created_at, $evento->ocurrido_at);
                if ($ultima !== null) {
                    $this->assertGreaterThan($ultima, $evento->ocurrido_at);
                }
                $ultima = $evento->ocurrido_at;
            }
            $cotizacion = DB::table('cotizaciones')->find($pedido->cotizacion_id);
            $this->assertLessThan($pedido->created_at, $cotizacion->aprobado_at);
        }
        $this->assertFalse(DB::table('clientes')->whereNotNull('email')->exists());
        foreach (DB::table('clientes')->get() as $cliente) {
            $this->assertTrue(str_starts_with($cliente->direccion, 'La Paz · ') || str_starts_with($cliente->direccion, 'El Alto · '));
        }
        foreach (DB::table('pedidos')->get() as $pedido) {
            $cliente = json_decode($pedido->datos_cliente_acordados, true);
            $this->assertNull($cliente['email']);
            $this->assertTrue(str_starts_with($cliente['direccion'], 'La Paz · ') || str_starts_with($cliente['direccion'], 'El Alto · '));
        }
        foreach (DB::table('casos_historicos')->get() as $caso) {
            $this->assertTrue(str_starts_with($caso->contexto, 'La Paz · ') || str_starts_with($caso->contexto, 'El Alto · '));
        }
        $this->assertGreaterThan(0, (float) DB::table('pedidos')->sum('precio_total_acordado') - (float) DB::table('pagos')->sum('importe'));
        $cuenta = $usuario->getAttributes();
        $auditorias = DB::table('auditorias')->get()->toJson();
        $this->artisan('migrate', ['--seed' => true])->assertSuccessful();
        $this->assertSame($cuenta, $usuario->fresh()->getAttributes());
        $this->assertSame($auditorias, DB::table('auditorias')->get()->toJson());
        $this->assertDatabaseCount('clientes', 36);
        $this->assertDatabaseCount('pedidos', 47);
        $caso = CasoHistorico::factory()->create();
        $this->assertSame(88, $caso->id);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('casos_historicos', 88);
        $this->assertSame(88, $caso->fresh()->id);
    }

    public function test_desactivado_carga_catalogos_sin_cuentas_ni_ejemplos(): void
    {
        config(['datos_sistema.habilitado' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('unidades_materiales', 6);
        $this->assertDatabaseCount('tipos_mueble', 9);
        $this->assertDatabaseCount('cotizaciones', 0);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_produccion_no_carga_ejemplos_aunque_se_active_la_opcion(): void
    {
        config(['datos_sistema.habilitado' => true]);
        app()->detectEnvironment(fn () => 'production');
        try {
            $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('cotizaciones', 0);
            $this->assertDatabaseCount('pedidos', 0);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_sin_interaccion_no_inventa_credenciales_del_propietario(): void
    {
        config(['datos_sistema.habilitado' => true]);
        try {
            Artisan::call('db:seed', ['--no-interaction' => true]);
            $this->fail('Debe requerir una cuenta de propietario configurada.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('No se creó el propietario', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('cotizaciones', 0);
    }
}
