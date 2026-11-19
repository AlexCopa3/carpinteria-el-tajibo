<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\CatalogoService;
use App\Services\ExistenciaMaterialService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditoriaGestionTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->actingAs($this->propietario);
    }

    private function material(): Material
    {
        return app(CatalogoService::class)->guardar($this->propietario, [
            'nombre' => 'Material auditado', 'categoria_id' => Categoria::create(['nombre' => 'Categoría de prueba'])->id,
            'unidad_id' => UnidadMaterial::where('codigo', 'unidad')->value('id'), 'precio_referencia' => '5', 'activo' => true,
        ]);
    }

    public function test_fichas_ver_concentran_responsables_y_edicion_no_muestra_historial(): void
    {
        $material = $this->material();
        $categoria = $material->categoriaMaterial;
        $this->post('/admin/clientes/create', ['nombre' => 'Cliente de consulta', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => '1'])->assertRedirect();
        $cliente = Cliente::sole();
        $rutas = [
            '/admin/clientes/'.$cliente->id,
            '/admin/materiales/'.$material->id,
            '/admin/categorias/'.$categoria->id,
            '/admin/usuarios/'.$this->propietario->id,
        ];
        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertOk()->assertSee('Responsables del registro')->assertDontSee('Ver historial completo')->assertDontSee('Historial de cambios');
            $this->get($ruta.'/edit')->assertOk()->assertDontSee('Responsables del registro')->assertDontSee('Primera modificación');
        }
        $this->get('/admin/categorias/'.$categoria->id)->assertSee($material->nombre);
        $this->get('/admin/usuarios/'.$this->propietario->id)->assertSee($this->propietario->email)->assertDontSee($this->propietario->getRawOriginal('password'));
        foreach (['clientes', 'materiales', 'categorias', 'usuarios'] as $modulo) {
            $this->get('/admin/'.$modulo)->assertOk()->assertSee('fas fa-eye', false);
        }
        $maestro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($maestro)->get('/admin/usuarios/'.$this->propietario->id)->assertForbidden();
        $this->get('/admin/categorias/'.$categoria->id)->assertOk()->assertDontSee('Editar');
        $otroMaterial = app(CatalogoService::class)->guardar($this->propietario, [...$material->only($material->getFillable()), 'nombre' => 'Otro material de consulta']);
    }

    public function test_registra_creador_primera_y_ultima_modificacion_sin_aceptar_actor_del_formulario(): void
    {
        $otro = User::factory()->create()->assignRole('propietario');
        $datos = ['nombre' => 'Cliente auditado', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => '1'];
        $this->post('/admin/clientes/create', $datos + ['usuario_id' => $otro->id])->assertRedirect();
        $cliente = Cliente::sole();
        $this->actingAs($otro)->put('/admin/clientes/'.$cliente->id, [...$datos, 'telefono' => '71111111'])->assertRedirect();
        $this->actingAs($this->propietario)->put('/admin/clientes/'.$cliente->id, [...$datos, 'telefono' => '72222222'])->assertRedirect();
        $eventos = $cliente->auditorias()->orderBy('id')->get();
        $this->assertCount(3, $eventos);
        $this->assertSame($this->propietario->id, $eventos[0]->usuario_id);
        $this->assertSame($otro->id, $eventos[1]->usuario_id);
        $this->assertSame($this->propietario->id, $eventos[2]->usuario_id);
        $this->assertSame('71111111', $eventos[2]->datos_anteriores['telefono']);
        $this->get('/admin/clientes/'.$cliente->id)->assertOk()->assertSee('Registrado')->assertSee('Primera modificación')->assertSee('Última modificación');
    }

    public function test_guardado_sin_cambios_no_duplica_auditoria_y_registro_anterior_no_inventa_creador(): void
    {
        $cliente = Cliente::create(['nombre' => 'Anterior', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => true]);
        $this->get('/admin/clientes/'.$cliente->id)->assertOk()->assertSee('No registrado anteriormente');
        $datos = $cliente->only($cliente->getFillable());
        $this->put('/admin/clientes/'.$cliente->id, $datos)->assertRedirect();
        $this->assertSame(0, $cliente->auditorias()->count());
    }

    public function test_elimina_cliente_sin_relaciones_y_conserva_el_evento_inmutable(): void
    {
        $this->post('/admin/clientes/create', ['nombre' => 'Creado por error', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => '1'])->assertRedirect();
        $cliente = Cliente::sole();
        $this->delete('/admin/clientes/'.$cliente->id)->assertRedirect();
        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
        $evento = Auditoria::where('accion', 'eliminado')->sole();
        $this->assertSame($cliente->id, $evento->registro_id);
        $this->assertSame($this->propietario->id, $evento->usuario_id);
        $this->assertSame('Creado por error', $evento->datos_anteriores['nombre']);
        $this->delete('/admin/clientes/'.$cliente->id)->assertNotFound();
        $this->assertSame(1, Auditoria::where('accion', 'eliminado')->count());
        $this->expectException(\LogicException::class);
        $evento->delete();
    }

    public function test_una_relacion_adicional_impide_el_borrado_y_revierte_el_evento(): void
    {
        $cliente = Cliente::create(['nombre' => 'Relacionado', 'tipo' => 'persona', 'telefono' => '70000000']);
        Schema::create('referencias_prueba', function ($tabla) {
            $tabla->id();
            $tabla->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
        });
        DB::table('referencias_prueba')->insert(['cliente_id' => $cliente->id]);
        $this->deleteJson('/admin/clientes/'.$cliente->id)->assertUnprocessable()->assertJsonValidationErrors('registro');
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
        $this->assertSame(0, Auditoria::where('accion', 'eliminado')->count());
    }

    public function test_material_vacio_puede_eliminarse_sin_dejar_saldo_huerfano(): void
    {
        $material = $this->material();
        $this->delete('/admin/materiales/'.$material->id)->assertRedirect();
        $this->assertDatabaseMissing('materiales', ['id' => $material->id]);
        $this->assertDatabaseMissing('existencias_materiales', ['material_id' => $material->id]);
        $this->assertDatabaseHas('auditorias', ['registro_type' => Material::class, 'registro_id' => $material->id, 'accion' => 'eliminado']);
    }

    public function test_historial_impide_eliminar_material_aunque_el_saldo_sea_cero(): void
    {
        $material = $this->material();
        $existencias = app(ExistenciaMaterialService::class);
        $datos = ['clave' => (string) Str::uuid(), 'cantidad' => '2', 'unidad_id' => $material->unidad_id, 'existencia_esperada' => '0', 'motivo' => 'Cantidad de prueba'];
        $operacion = $existencias->guardarCantidad($this->propietario, $material, $datos);
        $existencias->guardarCantidad($this->propietario, $material, array_replace($datos, ['clave' => (string) Str::uuid(), 'cantidad' => '0', 'existencia_esperada' => '2']), true);
        $this->assertSame('0.0000', $material->existencia->fresh()->cantidad);
        $this->deleteJson('/admin/materiales/'.$material->id)->assertUnprocessable()->assertJsonValidationErrors('registro');
        $this->assertDatabaseHas('materiales', ['id' => $material->id]);
        $this->assertSame(2, DB::table('movimientos_materiales')->count());
    }

    public function test_categoria_con_materiales_se_desactiva_pero_no_se_elimina(): void
    {
        $material = $this->material();
        $categoria = $material->categoriaMaterial;
        $this->put('/admin/categorias/'.$categoria->id, ['nombre' => $categoria->nombre, 'activo' => '0'])->assertRedirect();
        $this->deleteJson('/admin/categorias/'.$categoria->id)->assertUnprocessable();
        $this->get('/admin/materiales/create')->assertOk()->assertViewHas('categorias', fn ($datos) => $datos->isEmpty());
        $datos = $material->only($material->getFillable());
        $this->postJson('/admin/materiales/create', $datos)->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
        $this->put('/admin/materiales/'.$material->id, $datos)->assertRedirect();
        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'activo' => false]);
    }

    public function test_cuenta_inactiva_no_inicia_sesion_y_sesion_abierta_se_invalida(): void
    {
        $maestro = User::factory()->create(['password' => 'Password-Seguro-123!'])->assignRole('maestro');
        $this->put('/admin/usuarios/'.$maestro->id, ['name' => $maestro->name, 'email' => $maestro->email, 'rol' => 'maestro', 'activo' => '0'])->assertRedirect();
        $this->post('/logout');
        $this->post('/login', ['email' => $maestro->email, 'password' => 'Password-Seguro-123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $maestro->update(['activo' => true]);
        $this->actingAs($maestro);
        DB::table('users')->where('id', $maestro->id)->update(['activo' => false]);
        $this->get('/admin')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_propietario_no_puede_desactivarse_y_auditoria_no_guarda_contrasenas(): void
    {
        $this->putJson('/admin/usuarios/'.$this->propietario->id, ['name' => $this->propietario->name, 'email' => $this->propietario->email, 'rol' => 'propietario', 'activo' => false])->assertUnprocessable()->assertJsonValidationErrors('activo');
        $this->assertTrue($this->propietario->fresh()->activo);
        $this->post('/admin/usuarios/create', ['name' => 'Cuenta auditada', 'email' => 'auditoria@example.test', 'rol' => 'maestro', 'tipo_identificacion' => 'ci', 'numero_identificacion' => '90002001'])->assertRedirect();
        $usuario = User::where('email', 'auditoria@example.test')->firstOrFail();
        $evento = $usuario->auditorias()->sole();
        $this->assertArrayNotHasKey('password', $evento->datos_nuevos);
        $this->assertStringNotContainsString($usuario->getRawOriginal('password'), $evento->toJson());
        $this->assertStringNotContainsString('tj.90002001', $evento->toJson());
    }

    public function test_maestro_no_elimina_registros_ni_consulta_auditoria_administrativa(): void
    {
        $material = $this->material();
        $maestro = User::factory()->create()->assignRole('maestro');
        $maestro->givePermissionTo('materiales.modificar');
        $this->actingAs($maestro)->deleteJson('/admin/materiales/'.$material->id)->assertForbidden();
        $this->get('/admin/auditoria')->assertForbidden();
        $this->assertDatabaseHas('materiales', ['id' => $material->id]);
    }
}
