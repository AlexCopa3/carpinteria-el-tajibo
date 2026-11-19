<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Material;
use App\Models\MovimientoMaterial;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\CatalogoService;
use App\Services\ExistenciaMaterialService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogoSimplificadoTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;
    private array $datos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->datos = [
            'nombre' => 'Tablero de prueba',
            'categoria_id' => Categoria::create(['nombre' => 'Maderas'])->id,
            'unidad_id' => UnidadMaterial::where('codigo', 'unidad')->value('id'),
            'precio_referencia' => '120.50',
            'activo' => true,
        ];
        $this->actingAs($this->propietario);
    }

    public function test_catalogo_sin_existencias_ni_campos_de_stock(): void
    {
        $this->post(route('admin.materiales.store'), $this->datos)->assertSessionHasNoErrors()->assertRedirect();
        $material = Material::firstOrFail();
        $this->assertSame('120.500000', $material->precio_referencia);
        $this->assertDatabaseCount('existencias_materiales', 0);
        $this->get(route('admin.materiales.index'))->assertOk()->assertSee('Tablero de prueba')->assertDontSee('<th>Existencia</th>', false);
        foreach (['admin.materiales.create', 'admin.materiales.edit'] as $ruta) {
            $this->get(route($ruta, $ruta === 'admin.materiales.edit' ? $material : []))
                ->assertOk()->assertDontSee('name="stock_minimo"', false)->assertSee('Unidad de medida');
        }
        $this->get(route('admin.materiales.show', $material))->assertOk()->assertDontSee('<dt>Existencia</dt>', false)->assertDontSee('Ver historial de movimientos');
        $this->get('/admin')->assertOk()->assertDontSee('Materiales sin existencias')->assertDontSee('Entradas · últimos 30 días')->assertDontSee('href="'.url('/admin/inventario').'"', false);
    }

    public function test_editar_catalogo_conserva_existencias_movimientos_y_umbrales_anteriores(): void
    {
        // Preparar únicamente en SQLite un registro del esquema histórico.
        $catalogo = app(CatalogoService::class);
        $material = $catalogo->guardar($this->propietario, $this->datos + ['stock_minimo' => '5']);
        $material->stock_minimo = '5';
        $material->save();
        $operacion = app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $material, [
            'clave' => (string) Str::uuid(), 'cantidad' => '20', 'unidad_id' => $material->unidad_id,
            'existencia_esperada' => '0', 'motivo' => 'Registro histórico aislado',
        ]);
        $tablas = ['existencias_materiales', 'movimientos_materiales'];
        $anteriores = [];
        foreach ($tablas as $tabla) {
            $anteriores[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        $this->put(route('admin.materiales.update', $material), array_replace($this->datos, ['nombre' => 'Tablero actualizado']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('5.0000', $material->fresh()->stock_minimo);
        foreach ($tablas as $tabla) {
            $this->assertSame($anteriores[$tabla], DB::table($tabla)->orderBy('id')->get()->toJson());
        }
        $this->get(route('admin.materiales.show', $material))->assertOk()->assertSee('Tablero actualizado');
    }

    public function test_inventario_retirado_rechaza_consultas_y_escrituras_por_url_directa(): void
    {
        foreach (['/admin/inventario', '/admin/inventario/entradas/create', '/admin/inventario/ajustes/create', '/admin/inventario/movimientos'] as $ruta) {
            $this->get($ruta)->assertNotFound();
        }
        foreach (['/admin/inventario/entradas/resumen', '/admin/inventario/ajustes/resumen', '/admin/inventario/confirmar'] as $ruta) {
            $this->post($ruta, [])->assertNotFound();
        }
        $this->assertDatabaseCount('movimientos_materiales', 0);
        $this->assertDatabaseCount('movimientos_materiales', 0);
    }

    public function test_formulario_no_acepta_stock_oculto_en_una_peticion(): void
    {
        $this->post(route('admin.materiales.store'), $this->datos + ['stock_minimo' => '5'])->assertSessionHasErrors('stock_minimo');
        $this->assertDatabaseCount('materiales', 0);
    }

    public function test_maestro_consulta_costos_sin_administrar_catalogo(): void
    {
        $material = app(CatalogoService::class)->guardar($this->propietario, $this->datos);
        $maestro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($maestro);
        $this->get(route('admin.materiales.index'))->assertOk()->assertSee('120,50')->assertDontSee('Nuevo material');
        $this->get(route('admin.materiales.show', $material))->assertOk()->assertSee('Referencia por unidad');
        $this->post(route('admin.materiales.store'), $this->datos)->assertForbidden();
        $this->put(route('admin.materiales.update', $material), $this->datos)->assertForbidden();
        $this->get('/admin/inventario')->assertNotFound();
    }
}
