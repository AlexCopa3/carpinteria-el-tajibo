<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Material;
use App\Models\Pedido;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\CatalogoService;
use App\Services\ExistenciaMaterialService;
use App\Services\PlanificacionMaterialService;
use Database\Seeders\DatosSistemaSeeder;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeedersModulosTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        $this->propietario = User::factory()->create(['name' => 'Propietario previo'])->assignRole('propietario');
        User::factory()->create(['name' => 'Maestro previo'])->assignRole('maestro');
    }

    public function test_seeder_general_sin_activacion_en_testing_no_carga_ejemplos(): void
    {
        config(['datos_sistema.habilitado' => false]);
        try {
            $this->cargar(DatosSistemaSeeder::PRIMERA_CARGA);
            $this->fail('Debe bloquear.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('desactivada', $error->getMessage());
        }
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_rechaza_cualquier_otra_base_mysql(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $administrador = DB::getFacadeRoot();
        DB::shouldReceive('getDriverName')->once()->andReturn('mysql');
        DB::shouldReceive('selectOne')->once()->andReturn((object) ['nombre' => 'sisgestionescolar']);
        try {
            (new DatosSistemaSeeder)->run();
            $this->fail('Debe bloquear.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('no autorizada', $error->getMessage());
        } finally {
            DB::swap($administrador);
        }
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_agrega_datos_sin_cambiar_usuarios_permisos_material_o_cliente_previos(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $categoria = Categoria::factory()->create(['nombre' => 'Material principal']);
        $cliente = Cliente::factory()->create(['nombre' => 'Cliente previo']);
        $material = app(CatalogoService::class)->guardar($this->propietario, ['nombre' => 'Melamina', 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', 'pieza')->value('id'), 'precio_referencia' => '55', 'activo' => true]);
        app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $material, ['clave' => Str::uuid()->toString(), 'cantidad' => '2', 'unidad_id' => $material->unidad_id, 'existencia_esperada' => '0', 'motivo' => 'Existencia previa conservada']);
        $previos = [];
        foreach (['users', 'personal', 'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'clientes', 'materiales', 'existencias_materiales', 'movimientos_materiales'] as $tabla) {
            $previos[$tabla] = DB::table($tabla)->get()->map(fn ($fila) => json_encode((array) $fila))->all();
        }
        $atributos = $material->fresh()->getAttributes();
        $this->cargar(DatosSistemaSeeder::PRIMERA_CARGA);
        foreach ($previos as $tabla => $filas) {
            $despues = DB::table($tabla)->get()->map(fn ($fila) => json_encode((array) $fila))->all();
            foreach ($filas as $fila) {
                $this->assertContains($fila, $despues);
            }
        }
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('clientes', 13);
        $this->assertDatabaseCount('materiales', 12);
        $this->assertDatabaseCount('cotizaciones', 7);
        $this->assertDatabaseCount('pedidos', 3);
        $this->assertSame($atributos, $material->fresh()->getAttributes());
        $this->assertSame('2.0000', $material->fresh()->existencia->cantidad);
        $this->assertSame('Cliente previo', $cliente->fresh()->nombre);
        $this->assertNotNull(Material::where('nombre', 'Melamina estándar')->first());
        foreach (Cliente::where('id', '!=', $cliente->id)->get() as $nuevo) {
            $this->assertStringNotContainsString('[SIMULADO]', $nuevo->nombre);
            $this->assertStringNotContainsString('DEMO', $nuevo->nombre);
        }
        $lote = Auditoria::where('accion', 'datos_ejemplo_cargados')->sole();
        $this->assertNull($lote->usuario_id);
        $this->assertSame('simulado', $lote->datos_nuevos['origen']);
        $this->assertCount(12, $lote->datos_nuevos['ids']['clientes']);
        $this->assertSame(0, Cotizacion::where('estado', 'pendiente_costos')->count());
        $this->assertFalse(Pedido::whereIn('estado', ['terminado', 'entregado'])->exists());
        $this->assertTrue(Pedido::all()->contains(fn ($pedido) => collect(app(PlanificacionMaterialService::class)->disponibilidad($pedido))->contains(fn ($fila) => $fila['comprar']->isPositive())));
    }

    public function test_repetir_no_duplica_ni_sobrescribe_ediciones_posteriores(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $this->cargar(DatosSistemaSeeder::PRIMERA_CARGA);
        Cliente::first()->update(['nombre' => 'Nombre ajustado por el usuario']);
        $antes = [];
        foreach (['users', 'clientes', 'materiales', 'movimientos_materiales', 'cotizaciones', 'pedidos', 'auditorias'] as $tabla) {
            $antes[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        $this->cargar(DatosSistemaSeeder::PRIMERA_CARGA);
        foreach ($antes as $tabla => $filas) {
            $this->assertSame($filas, DB::table($tabla)->orderBy('id')->get()->toJson());
        }
        $this->assertSame(1, Auditoria::where('accion', 'datos_ejemplo_cargados')->count());
    }

    public function test_fallo_a_mitad_de_la_carga_revierte_todos_los_ejemplos(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $categoria = Categoria::factory()->create(['nombre' => 'Material principal', 'activo' => false]);
        try {
            $this->cargar(DatosSistemaSeeder::PRIMERA_CARGA);
            $this->fail('Debe rechazar categoría inactiva.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('inactiva', $error->getMessage());
        }
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('materiales', 0);
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('users', 2);
        $this->assertFalse($categoria->fresh()->activo);
    }

    public function test_ampliacion_conserva_primera_carga_y_no_duplica_tras_editar(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $this->cargar(DatosSistemaSeeder::PRIMERA_CARGA);
        $antes = [];
        foreach (['users', 'clientes', 'materiales', 'existencias_materiales', 'movimientos_materiales', 'cotizaciones', 'pedidos', 'auditorias'] as $tabla) {
            $antes[$tabla] = DB::table($tabla)->get()->map(fn ($fila) => json_encode((array) $fila))->all();
        }
        $this->cargar(DatosSistemaSeeder::SEGUNDA_CARGA);
        foreach ($antes as $tabla => $filas) {
            $despues = DB::table($tabla)->get()->map(fn ($fila) => json_encode((array) $fila))->all();
            foreach ($filas as $fila) {
                $this->assertContains($fila, $despues);
            }
        }
        foreach (['users' => 2, 'clientes' => 36, 'materiales' => 26, 'cotizaciones' => 22, 'pedidos' => 10, 'existencias_materiales' => 25, 'movimientos_materiales' => 25] as $tabla => $cantidad) {
            $this->assertDatabaseCount($tabla, $cantidad);
        }
        $this->assertSame(0, Cotizacion::where('estado', 'pendiente_costos')->count());
        $this->assertSame(14, Cotizacion::whereNotNull('aprobado_at')->count());
        $this->assertSame(6, Pedido::where('estado', 'en_fabricacion')->count());
        $this->assertFalse(Pedido::whereIn('estado', ['terminado', 'entregado'])->exists());
        $lote = Auditoria::where('accion', 'datos_ejemplo_cargados')->where('datos_nuevos->lote', 'datos_bolivianos_20261105_v2')->sole();
        $this->assertCount(24, $lote->datos_nuevos['ids']['clientes']);
        $this->assertSame('simulado', $lote->datos_nuevos['origen']);
        Cliente::findOrFail($lote->datos_nuevos['ids']['clientes'][0])->update(['nombre' => 'Nombre ajustado después de ampliar']);
        $actual = [];
        foreach (array_keys($antes) as $tabla) {
            $actual[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        $this->cargar(DatosSistemaSeeder::SEGUNDA_CARGA);
        foreach ($actual as $tabla => $filas) {
            $this->assertSame($filas, DB::table($tabla)->orderBy('id')->get()->toJson());
        }
        $this->assertSame(2, Auditoria::where('accion', 'datos_ejemplo_cargados')->count());
    }

    public function test_ampliacion_exige_carga_anterior_y_activacion_explicita(): void
    {
        config(['datos_sistema.habilitado' => true]);
        try {
            $this->cargar(DatosSistemaSeeder::SEGUNDA_CARGA);
            $this->fail('Debe exigir la primera carga.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Primero carga', $error->getMessage());
        }
        config(['datos_sistema.habilitado' => false]);
        try {
            $this->cargar(DatosSistemaSeeder::SEGUNDA_CARGA);
            $this->fail('Debe exigir activación.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('desactivada', $error->getMessage());
        }
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_ampliacion_revierte_clientes_si_falta_una_unidad(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $this->cargar(DatosSistemaSeeder::PRIMERA_CARGA);
        UnidadMaterial::where('codigo', 'kilogramo')->delete();
        $antes = Cliente::orderBy('id')->get()->toJson();
        try {
            $this->cargar(DatosSistemaSeeder::SEGUNDA_CARGA);
            $this->fail('No debe dejar clientes ni materiales parciales.');
        } catch (ModelNotFoundException $error) {
            $this->assertStringContainsString('UnidadMaterial', $error->getMessage());
        }
        $this->assertSame($antes, Cliente::orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('materiales', 11);
        $this->assertDatabaseCount('pedidos', 3);
        $this->assertSame(1, Auditoria::where('accion', 'datos_ejemplo_cargados')->count());
    }

    private function cargar(string $lote): void
    {
        $seeder = app(DatosSistemaSeeder::class);
        $seeder->setContainer(app());
        $seeder->cargar($lote);
    }

    public function test_coordinador_ejecuta_los_modulos_y_reconoce_ambas_cargas_anteriores(): void
    {
        config(['datos_sistema.habilitado' => true]);
        $this->seed(DatosSistemaSeeder::class);
        $this->assertDatabaseCount('clientes', 36);
        $this->assertDatabaseCount('materiales', 26);
        $this->assertDatabaseCount('cotizaciones', 22);
        $this->assertDatabaseCount('pedidos', 10);
        $antes = DB::table('auditorias')->get()->toJson();
        $this->seed(DatosSistemaSeeder::class);
        $this->assertSame($antes, DB::table('auditorias')->get()->toJson());
        foreach (['CategoriasSeeder', 'ClientesSeeder', 'MaterialesSeeder', 'ExistenciasMaterialesSeeder', 'CotizacionesSeeder', 'PedidosSeeder'] as $modulo) {
            $this->assertFileExists(database_path('seeders/'.$modulo.'.php'));
        }
    }
}
