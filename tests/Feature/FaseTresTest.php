<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\MovimientoMaterial;
use App\Models\UnidadMaterial;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FaseTresTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class]);
        $this->owner = User::factory()->create()->assignRole('propietario');
    }

    public function test_clientes_registro_edicion_busqueda_y_desactivacion_sin_borrar(): void
    {
        $d = ['nombre' => 'Cliente de prueba', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => '1'];
        $this->actingAs($this->owner)->post('/admin/clientes/create', $d)->assertRedirect(route('admin.clientes.index'));
        $this->post('/admin/clientes/create', $d)->assertRedirect();
        $this->assertSame(2, Cliente::count());
        $c = Cliente::first();
        $this->put('/admin/clientes/'.$c->id, [...$d, 'nombre' => 'Empresa de prueba', 'tipo' => 'empresa', 'activo' => '0'])->assertRedirect();
        $this->assertFalse($c->fresh()->activo);
        $this->assertSame(2, Cliente::count());
        $this->get('/admin/clientes?q=Empresa')->assertOk()->assertSee('Empresa de prueba');
        $this->get('/admin/clientes/'.$c->id)->assertOk()->assertSee('Cliente inactivo');
    }

    public function test_validaciones_cliente_y_restriccion_del_maestro(): void
    {
        $this->actingAs($this->owner)->postJson('/admin/clientes/create', ['nombre' => '', 'tipo' => 'otro', 'telefono' => ''])->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'tipo', 'telefono']);
        $master = User::factory()->create()->assignRole('maestro');
        $this->actingAs($master)->get('/admin/clientes')->assertOk();
        $this->get('/admin/clientes/create')->assertForbidden();
        $this->postJson('/admin/clientes/create', [])->assertForbidden();
        $cliente = Cliente::create(['nombre' => 'Cliente protegido', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => true]);
        $this->delete('/admin/clientes/'.$cliente->id)->assertForbidden();
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_material_registro_categoria_edicion_sin_presentaciones(): void
    {
        $u = UnidadMaterial::where('codigo', 'unidad')->first();
        $d = ['nombre' => 'Tornillo', 'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Herrajes'])->id, 'unidad_id' => $u->id, 'precio_referencia' => '0.35', 'activo' => '1'];
        $this->actingAs($this->owner)->post('/admin/materiales/create', $d)->assertRedirect();
        $m = Material::firstOrFail();
        $this->assertNull($m->existencia);
        $this->postJson('/admin/materiales/create', [...$d, 'categoria_id' => 999999])->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
        $this->put('/admin/materiales/'.$m->id, [...$d, 'especificaciones' => 'Acero 4 mm', 'precio_referencia' => '0.40'])->assertRedirect();
        $this->assertSame('Acero 4 mm', $m->fresh()->especificaciones);
        $this->post('/admin/materiales/'.$m->id.'/presentaciones/create', ['nombre' => 'Caja', 'factor' => '100', 'activo' => '1'])->assertNotFound();
        $this->assertFalse(Schema::hasTable('presentaciones_compra'));
        $this->get('/admin/materiales/'.$m->id)->assertOk()->assertDontSee('Presentaciones de compra');
    }

    public function test_unidades_sembradas_sin_materiales_de_demostracion(): void
    {
        $this->seed(UnidadesSeeder::class);
        $this->assertSame(6, UnidadMaterial::count());
        $this->assertSame(0, Material::count());
        $this->assertSame(0, MovimientoMaterial::count());
    }
}
