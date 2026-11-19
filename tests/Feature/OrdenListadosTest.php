<?php

namespace Tests\Feature;

use App\Models\CasoHistorico;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\TipoMueble;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\ExistenciaMaterialService;
use App\Services\PagoService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdenListadosTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->travelTo(now()->setDate(2026, 11, 17)->setTime(12, 0));
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        $this->actor = User::factory()->create()->assignRole('propietario');
        $this->actingAs($this->actor);
    }

    private function orden(string $url, string $variable, array $ids): void
    {
        $this->get($url)->assertOk()->assertViewHas($variable, fn ($lista) => $lista->pluck('id')->all() === $ids);
    }

    private function cotizacion(string $fecha): int
    {
        return DB::table('cotizaciones')->insertGetId(['clave_registro' => (string) Str::uuid(), 'solicitud_hash' => str_repeat('a', 64), 'responsable_id' => $this->actor->id, 'fecha' => $fecha, 'estado' => 'borrador', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function pedido(Cliente $cliente, string $fecha): int
    {
        return DB::table('pedidos')->insertGetId(['cotizacion_id' => $this->cotizacion($fecha), 'cliente_id' => $cliente->id, 'datos_cliente_acordados' => json_encode(['nombre' => $cliente->nombre]), 'fecha_aceptacion' => $fecha, 'registrado_por_id' => $this->actor->id, 'estado' => 'aceptado', 'precio_total_acordado' => '200', 'clave_registro' => (string) Str::uuid(), 'hash_solicitud' => str_repeat('b', 64), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_operaciones_se_ordenan_por_id_descendente_aunque_las_fechas_difieran(): void
    {
        $cotizaciones = [];
        foreach (['2026-11-01', '2026-11-12', '2020-03-01', '2026-11-12'] as $fecha) {
            $cotizaciones[] = $this->cotizacion($fecha);
        }
        $this->orden('/admin/cotizaciones', 'cotizaciones', [$cotizaciones[3], $cotizaciones[2], $cotizaciones[1], $cotizaciones[0]]);
        $cliente = Cliente::factory()->create();
        $pedidos = [];
        foreach (['2026-11-01', '2026-11-12', '2020-03-01', '2026-11-12'] as $fecha) {
            $pedidos[] = $this->pedido($cliente, $fecha);
        }
        $esperados = [$pedidos[3], $pedidos[2], $pedidos[1], $pedidos[0]];
        foreach (['/admin/pedidos', '/admin/planificacion', '/admin/pagos'] as $ruta) {
            $this->orden($ruta, 'pedidos', $esperados);
        }
        $this->orden('/admin/clientes/'.$cliente->id, 'pedidosCliente', $esperados);
    }

    public function test_casos_y_pagos_usan_id_descendente(): void
    {
        $casos = [];
        foreach (['2026-11-01', '2026-11-12', '2020-03-01', '2026-11-12'] as $fecha) {
            $casos[] = CasoHistorico::factory()->create(['fecha_referencia' => $fecha])->id;
        }
        $this->orden('/admin/casos', 'casos', [$casos[3], $casos[2], $casos[1], $casos[0]]);
        $this->get('/admin/casos?q='.$casos[0])->assertOk()
            ->assertViewHas('totalCasos', 4)
            ->assertViewHas('casos', fn ($lista) => $lista->total() === 1)
            ->assertSee('Resultados del filtro: 1');

        $cliente = Cliente::factory()->create();
        $id = $this->pedido($cliente, '2020-01-01');
        $pagos = [];
        foreach (['2026-11-01', '2026-11-12', '2020-03-01', '2026-11-12'] as $fecha) {
            $pagos[] = app(PagoService::class)->guardar($this->actor, ['pedido_id' => $id, 'fecha' => $fecha, 'importe' => '10', 'medio' => 'efectivo', 'clave_registro' => (string) Str::uuid()])->id;
        }
        $this->orden('/admin/pagos/pedido/'.$id, 'pagos', [$pagos[3], $pagos[2], $pagos[1], $pagos[0]]);
    }

    public function test_catalogos_usan_id_descendente_y_conservan_filtros_y_paginacion(): void
    {
        $clientes = [];
        $categorias = [];
        $materiales = [];
        $tipos = [];
        foreach ([['Z', '2026-11-12'], ['A', '2020-01-01'], ['M', '2026-11-12']] as $indice => [$nombre, $fecha]) {
            $clientes[] = Cliente::factory()->create(['nombre' => 'Orden '.$nombre, 'created_at' => $fecha])->id;
            $categorias[] = Categoria::factory()->create(['nombre' => 'Orden '.$nombre, 'created_at' => $fecha])->id;
            $materiales[] = Material::factory()->create(['nombre' => 'Orden '.$nombre, 'categoria_id' => $categorias[0], 'created_at' => $fecha])->id;
            $tipo = new TipoMueble;
            $tipo->codigo = 'orden_'.$indice;
            $tipo->nombre = 'Orden '.$nombre;
            $tipo->activo = true;
            $tipo->created_at = $fecha;
            $tipo->save();
            $tipos[] = $tipo->id;
        }
        $orden = fn ($ids) => [$ids[2], $ids[1], $ids[0]];
        foreach ([['/admin/clientes?q=Orden', 'clientes', $clientes], ['/admin/categorias?q=Orden', 'categorias', $categorias], ['/admin/materiales?q=Orden', 'materiales', $materiales], ['/admin/existencias?q=Orden', 'materiales', $materiales], ['/admin/tipos-mueble?q=Orden', 'tipos', $tipos], ['/admin/categorias/'.$categorias[0], 'materiales', $materiales]] as [$ruta, $variable, $ids]) {
            $this->orden($ruta, $variable, $orden($ids));
        }
        $otros = [];
        for ($i = 0; $i < 13; $i++) {
            $otros[] = Cliente::factory()->create(['nombre' => 'Orden '.$i, 'created_at' => '2026-11-12'])->id;
        }
        $esperados = [...array_reverse($otros), ...$orden($clientes)];
        $this->orden('/admin/clientes?q=Orden', 'clientes', array_slice($esperados, 0, 12));
        $this->orden('/admin/clientes?q=Orden&page=2', 'clientes', array_slice($esperados, 12));
    }

    public function test_usuarios_movimientos_auditoria_y_roles_se_ordenan_por_id_descendente(): void
    {
        DB::table('users')->where('id', $this->actor->id)->update(['created_at' => '2019-01-01']);
        $usuarios = [];
        $movimientos = [];
        $auditorias = [];
        $material = Material::factory()->create();
        foreach ([['Z', '2026-11-12'], ['A', '2020-01-01'], ['M', '2026-11-12']] as $indice => [$nombre, $fecha]) {
            $usuarios[] = User::factory()->create(['name' => $nombre, 'created_at' => $fecha])->assignRole('maestro')->id;
            $movimiento = app(ExistenciaMaterialService::class)->guardarCantidad($this->actor, $material, ['clave' => (string) Str::uuid(), 'cantidad' => '1', 'unidad_id' => $material->unidad_id, 'existencia_esperada' => (string) $indice, 'motivo' => 'Control de orden del listado.']);
            DB::table('movimientos_materiales')->where('id', $movimiento->id)->update(['created_at' => $fecha]);
            $movimientos[] = $movimiento->id;
            app(AuditoriaService::class)->registrar($this->actor, $material, 'creado', [], ['nombre' => $nombre]);
            $id = DB::table('auditorias')->max('id');
            DB::table('auditorias')->where('id', $id)->update(['created_at' => $fecha]);
            $auditorias[] = $id;
        }
        $this->orden('/admin/usuarios', 'usuarios', [$usuarios[2], $usuarios[1], $usuarios[0], $this->actor->id]);
        $this->orden('/admin/existencias/'.$material->id, 'movimientos', [$movimientos[2], $movimientos[1], $movimientos[0]]);
        $this->orden('/admin/auditoria', 'auditorias', [$auditorias[2], $auditorias[1], $auditorias[0]]);
        DB::table('roles')->where('name', 'propietario')->update(['created_at' => '2026-11-12']);
        DB::table('roles')->where('name', 'maestro')->update(['created_at' => '2020-01-01']);
        $this->get('/admin/permisos')->assertOk()->assertViewHas('roles', fn ($roles) => $roles->pluck('name')->all() === ['maestro', 'propietario']);
    }
}
