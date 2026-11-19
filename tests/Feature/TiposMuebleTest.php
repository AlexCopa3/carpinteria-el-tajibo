<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cotizacion;
use App\Models\PermisoUsuario;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\CatalogoService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TiposMuebleTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermisosSeeder::class, TiposMuebleSeeder::class, UnidadesSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
    }

    public function test_propietario_registra_tipo_sin_codigo_manual_y_con_auditoria(): void
    {
        $this->actingAs($this->propietario)->get('/admin/tipos-mueble/create')->assertOk()
            ->assertDontSee('name="codigo"', false);
        $this->post('/admin/tipos-mueble/create', ['nombre' => '  Biblioteca  ', 'activo' => 1, 'codigo' => 'mesa'])
            ->assertRedirect(route('admin.tipos_mueble.index'));
        $tipo = TipoMueble::where('nombre', 'Biblioteca')->firstOrFail();
        $this->assertTrue($tipo->activo);
        $this->assertNotSame('mesa', $tipo->codigo);
        $this->assertLessThanOrEqual(30, strlen($tipo->codigo));
        $this->assertSame($this->propietario->id, $tipo->auditorias()->sole()->usuario_id);
        $this->get('/admin/cotizaciones/create')->assertOk()->assertViewHas('tipos', fn ($tipos) => $tipos->contains('id', $tipo->id));
        $this->get('/admin/tipos-mueble/'.$tipo->id)->assertOk()->assertSee('Biblioteca')->assertSee('Responsables del registro');
    }

    public function test_nombre_estado_y_unicidad_se_validan_en_servidor(): void
    {
        $this->actingAs($this->propietario);
        foreach ([['nombre' => '', 'activo' => 1], ['nombre' => str_repeat('a', 81), 'activo' => 1], ['nombre' => 'Mesa', 'activo' => 1], ['nombre' => '  Mesa  ', 'activo' => 1]] as $datos) {
            $this->postJson('/admin/tipos-mueble/create', $datos)->assertUnprocessable()->assertJsonValidationErrors('nombre');
        }
        $this->postJson('/admin/tipos-mueble/create', ['nombre' => 'Biblioteca', 'activo' => 3])->assertUnprocessable()->assertJsonValidationErrors('activo');
        $this->postJson('/admin/tipos-mueble/create', ['nombre' => 'Biblioteca'])->assertUnprocessable()->assertJsonValidationErrors('activo');
        $this->assertDatabaseCount('tipos_mueble', 9);
    }

    public function test_edicion_conserva_codigo_y_el_seeder_no_revierte_nombre_o_estado(): void
    {
        $tipo = TipoMueble::where('codigo', 'mesa')->firstOrFail();
        $this->actingAs($this->propietario)->put('/admin/tipos-mueble/'.$tipo->id, ['nombre' => 'Mesa de comedor', 'activo' => 0, 'codigo' => 'alterado'])
            ->assertRedirect(route('admin.tipos_mueble.index'));
        $this->seed(TiposMuebleSeeder::class);
        $tipo->refresh();
        $this->assertSame('mesa', $tipo->codigo);
        $this->assertSame('Mesa de comedor', $tipo->nombre);
        $this->assertFalse($tipo->activo);
        $this->assertDatabaseCount('tipos_mueble', 9);
        $this->assertSame('modificado', $tipo->auditorias()->sole()->accion);
        $this->get('/admin/cotizaciones/create')->assertViewHas('tipos', fn ($tipos) => ! $tipos->contains('id', $tipo->id));
        $this->put('/admin/tipos-mueble/'.$tipo->id, ['nombre' => 'Mesa de comedor', 'activo' => 1])->assertRedirect();
        $this->get('/admin/cotizaciones/create')->assertViewHas('tipos', fn ($tipos) => $tipos->contains('id', $tipo->id));
    }

    public function test_maestro_consulta_pero_no_administra_por_defecto(): void
    {
        $maestro = User::factory()->create()->assignRole('maestro');
        $tipo = TipoMueble::firstOrFail();
        $this->actingAs($maestro)->get('/admin/tipos-mueble')->assertOk()->assertDontSee('Registrar tipo de mueble');
        $this->get('/admin/tipos-mueble/'.$tipo->id)->assertOk();
        $this->get('/admin/tipos-mueble/create')->assertForbidden();
        $this->get('/admin/tipos-mueble/'.$tipo->id.'/edit')->assertForbidden();
        $this->postJson('/admin/tipos-mueble/create', ['nombre' => 'Alterado', 'activo' => 1])->assertForbidden();
        $this->putJson('/admin/tipos-mueble/'.$tipo->id, ['nombre' => 'Alterado', 'activo' => 0])->assertForbidden();
        $this->assertDatabaseCount('tipos_mueble', 9);
    }

    public function test_administracion_delegable_y_denegacion_individual_prevalece(): void
    {
        $maestro = User::factory()->create()->assignRole('maestro');
        $maestro->givePermissionTo('configuracion.gestionar');
        $this->actingAs($maestro->fresh())->post('/admin/tipos-mueble/create', ['nombre' => 'Velador', 'activo' => 1])->assertRedirect();
        PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => Permission::findByName('configuracion.gestionar')->id, 'permitido' => false]);
        $this->actingAs($maestro->fresh())->postJson('/admin/tipos-mueble/create', ['nombre' => 'Biblioteca', 'activo' => 1])->assertForbidden();
        $this->assertDatabaseHas('tipos_mueble', ['nombre' => 'Velador']);
        $this->assertDatabaseMissing('tipos_mueble', ['nombre' => 'Biblioteca']);
    }

    public function test_busqueda_paginada_y_no_hay_eliminacion(): void
    {
        foreach (range(1, 13) as $numero) {
            TipoMueble::create(['nombre' => 'Mueble especial '.$numero, 'codigo' => 'especial-'.$numero, 'activo' => true]);
        }
        $this->actingAs($this->propietario)->get('/admin/tipos-mueble?q=especial')->assertOk()
            ->assertViewHas('tipos', fn ($tipos) => $tipos->total() === 13 && count($tipos) === 12)->assertSee('q=especial', false);
        $this->get('/admin/tipos-mueble?q=especial&page=2')->assertOk()->assertViewHas('tipos', fn ($tipos) => count($tipos) === 1);
        $this->getJson('/admin/tipos-mueble?q[]=Mesa')->assertUnprocessable();
        $this->delete('/admin/tipos-mueble/1')->assertStatus(405);
        $this->assertDatabaseCount('tipos_mueble', 22);
    }

    public function test_desactivar_tipo_no_reescribe_cotizacion_aprobada_ni_permite_nuevas_con_tipo_inactivo(): void
    {
        $tipo = TipoMueble::where('codigo', 'mesa')->firstOrFail();
        $categoria = Categoria::create(['nombre' => 'Material principal']);
        $material = app(CatalogoService::class)->guardar($this->propietario, ['nombre' => 'Madera de prueba', 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', 'unidad')->value('id'), 'precio_referencia' => '40', 'activo' => true]);
        $datos = ['clave_registro' => Str::uuid()->toString(), 'fecha' => today()->format('Y-m-d'), 'detalles' => [[
            'tipo_mueble_id' => $tipo->id, 'material_principal_id' => $material->id, 'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75', 'cantidad_muebles' => 1,
            'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural', 'mano_obra_unitaria' => '20', 'otros_costos_total' => '5',
            'materiales' => [['material_id' => $material->id, 'componente' => 'General', 'cantidad_unitaria' => '2', 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo', 'costo_unitario_estimado' => '40']],
        ]]];
        $this->actingAs($this->propietario)->post('/admin/cotizaciones/create', $datos)->assertRedirect();
        $cotizacion = Cotizacion::sole();
        $detalle = $cotizacion->detalles()->sole();
        $this->post('/admin/cotizaciones/'.$cotizacion->id.'/aprobar', ['version' => $cotizacion->version, 'precios' => [$detalle->id => '150']])->assertRedirect();
        $this->put('/admin/tipos-mueble/'.$tipo->id, ['nombre' => 'Mesa actualizada', 'activo' => 0])->assertRedirect();
        $this->assertSame('Mesa', $detalle->fresh()->tipo_nombre);
        $this->assertSame($tipo->id, $detalle->fresh()->tipo_mueble_id);
        $this->assertSame('mesa', $tipo->fresh()->codigo);
        $this->get('/admin/cotizaciones/'.$cotizacion->id)->assertOk()->assertSee('Mesa');
        $datos['clave_registro'] = Str::uuid()->toString();
        $this->postJson('/admin/cotizaciones/create', $datos)->assertUnprocessable();
        $this->assertDatabaseCount('cotizaciones', 1);
    }

    public function test_nuevo_tipo_permite_cotizar_y_se_conservan_los_permisos_existentes(): void
    {
        $this->assertDatabaseCount('permissions', 50);
        $this->actingAs($this->propietario)->post('/admin/tipos-mueble/create', ['nombre' => 'Ropero', 'activo' => 1])->assertRedirect();
        $tipo = TipoMueble::where('nombre', 'Ropero')->firstOrFail();
        $this->get('/admin/cotizaciones/create')->assertOk()->assertViewHas('tipos', fn ($tipos) => $tipos->contains('id', $tipo->id));
        $this->assertDatabaseCount('permissions', 50);
        $this->get('/admin/tipos-mueble')->assertSee('Tipos de muebles');
    }

    public function test_invitado_e_inactivo_no_acceden(): void
    {
        $this->get('/admin/tipos-mueble')->assertRedirect('/login');
        $inactivo = User::factory()->create(['activo' => false])->assignRole('propietario');
        $this->actingAs($inactivo)->get('/admin/tipos-mueble')->assertRedirect('/login');
        $this->assertGuest();
    }
}
