<?php

namespace Tests\Feature;

use App\Models\CasoHistorico;
use App\Models\User;
use App\Services\AuditoriaService;
use Brick\Math\BigDecimal;
use Database\Seeders\CasosHistoricosSeeder;
use Database\Seeders\DatosSistemaSeeder;
use Database\Seeders\FabricacionSeeder;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CasosPerfil;
use Tests\TestCase;

class CasosSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        User::factory()->create()->assignRole('propietario');
        User::factory()->create()->assignRole('maestro');
        config(['datos_sistema.habilitado' => true]);
        $this->seed(DatosSistemaSeeder::class);
    }

    public function test_genera_87_casos_desde_pedidos_con_uno_dos_tres_y_cuatro_detalles(): void
    {
        $antes = $this->snapshot(['users', 'personal', 'roles', 'permissions', 'clientes', 'materiales', 'existencias_materiales', 'movimientos_materiales', 'pagos']);
        app(CasosHistoricosSeeder::class)->run();
        $this->assertDatabaseCount('casos_historicos', 87);
        $this->assertDatabaseCount('pedidos', 47);
        $this->assertDatabaseCount('cotizaciones', 59);
        $this->assertFalse(CasoHistorico::whereNull('detalle_pedido_id')->exists());
        $this->assertSame(87, CasoHistorico::where('fuente', 'pedido')->where('origen', 'simulado')->where('estado_revision', 'pendiente')->count());
        $this->assertSame(87, CasoHistorico::distinct()->count('detalle_pedido_id'));
        $this->assertFalse(DB::table('materiales_caso')->whereNull('material_pedido_id')->exists());
        $grupos = DB::table('casos_historicos')->join('detalles_pedido', 'detalles_pedido.id', '=', 'casos_historicos.detalle_pedido_id')->selectRaw('detalles_pedido.pedido_id, COUNT(*) as cantidad')->groupBy('detalles_pedido.pedido_id')->get();
        $distribucion = array_count_values($grupos->pluck('cantidad')->map(fn ($cantidad) => (int) $cantidad)->all());
        ksort($distribucion);
        $this->assertSame([1 => 12, 2 => 12, 3 => 9, 4 => 6], $distribucion);
        foreach (CasoHistorico::with('detallePedido.pedido', 'materiales.materialPedido')->get() as $caso) {
            $this->assertContains($caso->detallePedido->pedido->estado, ['terminado', 'entregado']);
            $this->assertSame($caso->cantidad_muebles, $caso->detallePedido->cantidad_muebles);
            foreach ($caso->materiales as $material) {
                $this->assertTrue(BigDecimal::of($material->cantidad_total)->isEqualTo($material->materialPedido->cantidad_utilizada));
            }
        }
        $this->assertSame($antes, $this->snapshot(array_keys($antes)));
    }

    public function test_reemplaza_los_25_sin_pedido_y_conserva_los_dos_casos_anteriores(): void
    {
        app(FabricacionSeeder::class)->run();
        $previos = CasoHistorico::orderBy('id')->get()->keyBy('id')->toArray();
        CasosPerfil::cargar(false);
        $retirados = CasoHistorico::whereNull('detalle_pedido_id')->pluck('id')->all();
        app(CasosHistoricosSeeder::class)->run();
        $this->assertDatabaseCount('casos_historicos', 87);
        $this->assertFalse(CasoHistorico::whereIn('id', $retirados)->exists());
        $this->assertSame($previos, CasoHistorico::whereIn('id', array_keys($previos))->orderBy('id')->get()->keyBy('id')->toArray());
        $this->assertSame(25, CasoHistorico::where('lote', CasosHistoricosSeeder::LOTE)->whereNotNull('detalle_pedido_id')->count());
    }

    public function test_repetir_conserva_revisiones_y_no_duplica_pedidos_casos_o_auditorias(): void
    {
        app(CasosHistoricosSeeder::class)->run();
        $caso = CasoHistorico::first();
        $this->actingAs(User::role('propietario')->first())->postJson(route('admin.casos.revisar', $caso), ['version' => $caso->version, 'accion' => 'precio', 'confirmacion' => true])->assertRedirect();
        $antes = $this->snapshot(['casos_historicos', 'materiales_caso', 'pedidos', 'detalles_pedido', 'materiales_pedido', 'estados_pedido', 'cotizaciones', 'auditorias']);
        app(CasosHistoricosSeeder::class)->run();
        $this->assertSame($antes, $this->snapshot(array_keys($antes)));
    }

    public function test_catalogo_incompatible_aborta_sin_crear_o_modificar_pedidos(): void
    {
        DB::table('materiales')->where('nombre', 'Melamina')->update(['unidad_id' => DB::table('unidades_materiales')->where('codigo', 'metro')->value('id')]);
        $antes = $this->snapshot(['pedidos', 'cotizaciones', 'casos_historicos', 'materiales_caso', 'auditorias']);
        try {
            app(CasosHistoricosSeeder::class)->run();
            $this->fail('Debe rechazar la unidad.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Unidad o categoría incompatible', $error->getMessage());
        }
        $this->assertSame($antes, $this->snapshot(array_keys($antes)));
    }

    public function test_produccion_y_desactivacion_impiden_la_generacion(): void
    {
        $antes = $this->snapshot(['pedidos', 'cotizaciones', 'auditorias']);
        foreach ([['testing', false], ['production', true]] as [$entorno, $habilitado]) {
            config(['datos_sistema.habilitado' => $habilitado]);
            app()->detectEnvironment(fn () => $entorno);
            try {
                app(CasosHistoricosSeeder::class)->run();
                $this->fail('La carga debe estar bloqueada.');
            } catch (\RuntimeException $error) {
                $this->assertStringContainsString('desactivada', $error->getMessage());
            } finally {
                app()->detectEnvironment(fn () => 'testing');
            }
        }
        $this->assertSame($antes, $this->snapshot(array_keys($antes)));
    }

    public function test_fallo_de_auditoria_revierte_la_generacion_completa(): void
    {
        $antes = $this->snapshot(['pedidos', 'detalles_pedido', 'cotizaciones', 'casos_historicos', 'materiales_caso', 'auditorias']);
        $auditoria = \Mockery::mock(AuditoriaService::class)->makePartial();
        $auditoria->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Rollback aislado'));
        $this->app->instance(AuditoriaService::class, $auditoria);
        try {
            app(CasosHistoricosSeeder::class)->run();
            $this->fail('Debe revertir.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Rollback aislado', $error->getMessage());
        }
        $this->assertSame($antes, $this->snapshot(array_keys($antes)));
    }

    private function snapshot(array $tablas): array
    {
        $datos = [];
        foreach ($tablas as $tabla) {
            $filas = DB::table($tabla)->get()->map(fn ($fila) => json_encode((array) $fila))->all();
            sort($filas, SORT_STRING);
            $datos[$tabla] = $filas;
        }

        return $datos;
    }
}
