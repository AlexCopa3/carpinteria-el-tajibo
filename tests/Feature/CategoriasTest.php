<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Material;
use App\Models\UnidadMaterial;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoriasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class]);
    }

    public function test_propietario_registra_edita_y_selecciona_categoria_sin_codigo(): void
    {
        $usuario = User::factory()->create()->assignRole('propietario');
        $this->actingAs($usuario)->post('/admin/categorias/create', ['nombre' => 'Herrajes'])->assertRedirect(route('admin.categorias.index'));
        $categoria = Categoria::sole();
        $this->postJson('/admin/categorias/create', ['nombre' => 'Herrajes'])->assertUnprocessable()->assertJsonValidationErrors('nombre');
        $datos = ['nombre' => 'Tornillo', 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', 'unidad')->value('id'), 'precio_referencia' => '0.35', 'activo' => '1'];
        $this->get('/admin/materiales/create')->assertOk()->assertSee('name="categoria_id"', false)->assertDontSee('Código único')->assertDontSee('name="codigo"', false);
        $this->post('/admin/materiales/create', $datos)->assertRedirect();
        $material = Material::sole();
        $this->assertSame($categoria->id, $material->categoriaMaterial->id);
        $this->assertFalse(Schema::hasColumn('materiales', 'codigo'));
        $this->assertFalse(Schema::hasColumn('movimientos_materiales', 'material_codigo'));
        $this->put('/admin/categorias/'.$categoria->id, ['nombre' => 'Herrajes actualizados'])->assertRedirect();
        $this->get('/admin/materiales?q=actualizados')->assertOk()->assertSee('Tornillo')->assertSee('Herrajes actualizados');
        $this->postJson('/admin/materiales/create', [...$datos, 'categoria_id' => 999999])->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
        $this->assertNull($material->existencia);
    }

    public function test_maestro_consulta_categorias_pero_no_las_administra_incluso_con_permiso_erroneo(): void
    {
        $categoria = Categoria::create(['nombre' => 'Maderas']);
        $maestro = User::factory()->create()->assignRole('maestro');
        $maestro->givePermissionTo('materiales.modificar');
        $this->actingAs($maestro)->get('/admin/categorias')->assertOk()->assertDontSee('Registrar categoría');
        $this->get('/admin/categorias/create')->assertForbidden();
        $this->postJson('/admin/categorias/create', ['nombre' => 'Otra'])->assertForbidden();
        $this->putJson('/admin/categorias/'.$categoria->id, ['nombre' => 'Alterada'])->assertForbidden();
        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'nombre' => 'Maderas']);
    }

    public function test_busqueda_por_nombre_conserva_filtro_al_paginar_y_limpiar_recupera_todas(): void
    {
        $propietario = User::factory()->create()->assignRole('propietario');
        for ($indice = 0; $indice < 13; $indice++) {
            Categoria::create(['nombre' => 'Herrajes '.str_pad((string) $indice, 2, '0', STR_PAD_LEFT)]);
        }
        Categoria::create(['nombre' => 'Acabados']);
        $this->actingAs($propietario)->get('/admin/categorias?q=Herrajes')->assertOk()
            ->assertSee('Buscar categoría por nombre')->assertSee('Limpiar')->assertDontSee('Acabados')
            ->assertSee('q=Herrajes', false)
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 13 && count($categorias) === 12);
        $this->get('/admin/categorias?q=Herrajes&page=2')->assertOk()
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 13 && count($categorias) === 1);
        $this->get('/admin/categorias')->assertOk()->assertSee('Acabados')
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 14);
        $this->get('/admin/categorias?q=%20%20')->assertOk()
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 14);
        $this->assertDatabaseCount('categorias', 14);
    }

    public function test_maestro_busca_sin_administrar_y_consulta_invalida_se_rechaza(): void
    {
        Categoria::create(['nombre' => 'Maderas']);
        Categoria::create(['nombre' => 'Adhesivos']);
        $maestro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($maestro)->get('/admin/categorias?q=Mader')->assertOk()->assertSee('Maderas')
            ->assertDontSee('Adhesivos')->assertDontSee('Registrar categoría')
            ->assertViewHas('categorias', fn ($categorias) => $categorias->total() === 1);
        $this->get('/admin/categorias?q=NoExiste')->assertOk()->assertSee('No se encontraron categorías');
        $this->getJson('/admin/categorias?q='.str_repeat('a', 201))->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/admin/categorias?q[]=Maderas')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->assertDatabaseCount('categorias', 2);
    }
}
