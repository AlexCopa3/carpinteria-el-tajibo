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

class ExistenciasMaterialTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;
    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->material = app(CatalogoService::class)->guardar($this->propietario, [
            'nombre' => 'Barniz de prueba', 'categoria_id' => Categoria::create(['nombre' => 'Acabados'])->id,
            'unidad_id' => UnidadMaterial::where('codigo', 'litro')->value('id'), 'precio_referencia' => '50', 'activo' => true,
        ]);
        $this->actingAs($this->propietario);
    }

    public function test_modulo_muestra_materiales_sin_saldo_y_busca_por_categoria(): void
    {
        $this->get(route('admin.existencias.index'))->assertOk()->assertViewIs('admin.existencias.index')
            ->assertSee('Barniz de prueba')->assertSee('0 Litro')->assertSee('Agregar cantidad')->assertSee('Editar cantidad');
        $this->get(route('admin.existencias.index', ['q' => 'Acabados']))->assertOk()->assertSee('Barniz de prueba');
        $this->get(route('admin.existencias.index', ['q' => 'inexistente']))->assertOk()->assertDontSee('Barniz de prueba')->assertSee('No hay materiales para mostrar');
        $this->assertDatabaseCount('existencias_materiales', 0);
        $this->get(route('admin.materiales.index'))->assertOk()->assertDontSee('<th>Existencia</th>', false);
    }

    public function test_maestro_accede_al_modulo_sin_acciones_y_el_menu_lo_enlaza(): void
    {
        $maestro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($maestro)->get(route('admin.existencias.index'))->assertOk()->assertSee('Barniz de prueba')
            ->assertDontSee('Agregar cantidad')->assertDontSee('Editar cantidad');
        $this->get('/admin')->assertOk()->assertSee(route('admin.existencias.index'));
        $this->get('/admin/materiales/'.$this->material->id.'/cantidad/create')->assertNotFound();
        $this->assertSame('existencias_materiales', (new \App\Models\ExistenciaMaterial)->getTable());
    }

    public function test_consulta_muestra_solo_movimientos_del_material_y_no_cambia_saldos(): void
    {
        $service = app(ExistenciaMaterialService::class);
        $service->guardarCantidad($this->propietario, $this->material, $this->datos('2.5'));
        $service->guardarCantidad($this->propietario, $this->material, array_replace($this->datos('1', '2.5'), ['motivo' => 'Recuento físico del taller']), true);
        $otro = app(CatalogoService::class)->guardar($this->propietario, array_replace($this->material->only($this->material->getFillable()), ['nombre' => 'Otro material']));
        $service->guardarCantidad($this->propietario, $otro, array_replace($this->datos('4'), ['motivo' => 'Movimiento exclusivo del otro material']));
        $this->get(route('admin.existencias.index'))->assertOk()->assertSee('Ver movimientos');
        $this->get(route('admin.existencias.show', $this->material))->assertOk()->assertViewIs('admin.existencias.show')
            ->assertSee('Recuento físico del taller')->assertSee($this->propietario->name)->assertSee('Disminución')->assertSee('Aumento')
            ->assertDontSee('Movimiento exclusivo del otro material')->assertViewHas('movimientos', fn ($rows) => $rows->total() === 2);
        $this->assertSame('1.0000', $this->material->fresh()->existencia->cantidad);
        $this->assertDatabaseCount('movimientos_materiales', 3);
    }

    public function test_movimientos_son_consultables_por_maestro_y_protegidos_por_servidor(): void
    {
        app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material, $this->datos('2'));
        $ruta = route('admin.existencias.show', $this->material);
        $maestro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($maestro)->get($ruta)->assertOk()->assertSee('Recuento de prueba aislado')
            ->assertDontSee('Agregar cantidad')->assertDontSee('Editar cantidad');
        $this->post($ruta, [])->assertStatus(405);
        $this->put(route('admin.existencias.update', $this->material), $this->datos('0', '2'))->assertForbidden();
        $sinPermiso = User::factory()->create();
        $sinPermiso->givePermissionTo('panel.consultar');
        $this->actingAs($sinPermiso)->get($ruta)->assertForbidden();
        $this->actingAs($this->propietario)->get('/admin/existencias/999999')->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->get($ruta)->assertRedirect(route('login'));
        $this->assertDatabaseCount('movimientos_materiales', 1);
    }

    public function test_movimientos_vacios_y_paginacion_del_mas_reciente_al_antiguo(): void
    {
        $ruta = route('admin.existencias.show', $this->material);
        $this->get($ruta)->assertOk()->assertSee('Aún no hay movimientos');
        for ($numero = 1; $numero <= 16; $numero++) {
            app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material,
                array_replace($this->datos('1'), ['motivo' => 'Movimiento número '.$numero]));
        }
        $this->get($ruta)->assertOk()->assertViewHas('movimientos', fn ($rows) => $rows->total() === 16
            && $rows->count() === 15 && $rows->first()->motivo === 'Movimiento número 16');
        $this->get($ruta.'?page=2')->assertOk()->assertViewHas('movimientos', fn ($rows) => $rows->count() === 1
            && $rows->first()->motivo === 'Movimiento número 1');
    }

    private function datos(string $cantidad, string $esperada = '0'): array
    {
        return ['clave' => (string) Str::uuid(), 'cantidad' => $cantidad, 'unidad_id' => $this->material->unidad_id,
            'existencia_esperada' => $esperada, 'motivo' => 'Recuento de prueba aislado'];
    }

    public function test_agrega_sin_presentacion_y_reenvio_no_duplica_el_movimiento(): void
    {
        $datos = $this->datos('2.5');
        $ruta = route('admin.existencias.store', $this->material);
        $this->post($ruta, $datos)->assertSessionHasNoErrors()->assertRedirect(route('admin.existencias.index', ['material_id' => $this->material->id]));
        $this->post($ruta, $datos)->assertSessionHasNoErrors()->assertRedirect(route('admin.existencias.index', ['material_id' => $this->material->id]));
        $this->assertSame('2.5000', $this->material->fresh()->existencia->cantidad);
        $this->assertSame('50.000000', $this->material->fresh()->precio_referencia);
        $this->assertDatabaseCount('movimientos_materiales', 1);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('entradas_materiales'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('presentaciones_compra'));
        $this->assertDatabaseHas('movimientos_materiales', ['usuario_id' => $this->propietario->id, 'motivo' => $datos['motivo']]);
        $this->assertDatabaseHas('movimientos_materiales', ['estado_costo' => 'no_aplica', 'costo_total' => null]);
        $this->post($ruta, array_replace($datos, ['cantidad' => '3']))->assertSessionHasErrors('clave');
        $this->assertDatabaseCount('movimientos_materiales', 1);
    }

    public function test_corregir_registra_diferencia_y_permite_cero_con_reenvio_seguro(): void
    {
        app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material, $this->datos('5'));
        $datos = $this->datos('2', '5');
        $ruta = route('admin.existencias.update', $this->material);
        $this->put($ruta, $datos)->assertSessionHasNoErrors()->assertRedirect(route('admin.existencias.index', ['material_id' => $this->material->id]));
        $this->put($ruta, $datos)->assertSessionHasNoErrors()->assertRedirect(route('admin.existencias.index', ['material_id' => $this->material->id]));
        $this->assertDatabaseHas('movimientos_materiales', ['tipo' => 'ajuste_negativo', 'cantidad' => '3', 'existencia_anterior' => '5', 'existencia_resultante' => '2']);
        $this->put($ruta, $this->datos('0', '2'))->assertSessionHasNoErrors()->assertRedirect(route('admin.existencias.index', ['material_id' => $this->material->id]));
        $this->assertSame('0.0000', $this->material->fresh()->existencia->cantidad);
        $this->assertDatabaseCount('movimientos_materiales', 3);
    }

    public function test_correccion_obsoleta_no_sobrescribe_otro_cambio(): void
    {
        $datosViejos = $this->datos('2');
        app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material, $this->datos('5'));
        $this->put(route('admin.existencias.update', $this->material), $datosViejos)->assertSessionHasErrors('cantidad');
        $this->assertSame('5.0000', $this->material->fresh()->existencia->cantidad);
        $this->assertDatabaseCount('movimientos_materiales', 1);
    }

    public function test_validaciones_de_precision_limites_unidad_y_motivo(): void
    {
        $ruta = route('admin.existencias.store', $this->material);
        foreach (['0', '-1', '1e3', '0.00001', '999999999999999.99'] as $cantidad) {
            $this->post($ruta, $this->datos($cantidad))->assertSessionHasErrors('cantidad');
        }
        $this->post($ruta, array_replace($this->datos('2'), ['motivo' => '     ']))->assertSessionHasErrors('motivo');
        $this->post($ruta, array_replace($this->datos('2'), ['unidad_id' => 999]))->assertSessionHasErrors('cantidad');
        $this->material->unidad_id = UnidadMaterial::where('codigo', 'tablero')->value('id');
        $this->material->save();
        $this->post($ruta, $this->datos('1.5'))->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('movimientos_materiales', 0);
        $this->assertDatabaseCount('existencias_materiales', 0);
    }

    public function test_maestro_consulta_existencias_y_costos_sin_administrarlos(): void
    {
        app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material, $this->datos('3'));
        $maestro = User::factory()->create()->assignRole('maestro');
        $maestro->givePermissionTo(['materiales.modificar', 'inventario.gestionar', 'inventario.ajustes.crear']);
        \App\Models\PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => \Spatie\Permission\Models\Permission::findByName('inventario.gestionar')->id, 'permitido' => false]);
        $maestro->unsetRelation('permisosIndividuales');
        $this->actingAs($maestro);
        $this->get(route('admin.materiales.show', $this->material))->assertOk()->assertSee('Ver existencias')->assertSee('50,00')->assertDontSee('Agregar cantidad')->assertDontSee('Editar cantidad')->assertDontSee('Presentaciones de compra');
        $this->get(route('admin.existencias.create', $this->material))->assertForbidden();
        $this->get(route('admin.existencias.edit', $this->material))->assertForbidden();
        $this->post(route('admin.existencias.store', $this->material), $this->datos('2'))->assertForbidden();
        $this->put(route('admin.existencias.update', $this->material), $this->datos('0', '3'))->assertForbidden();
        $this->assertDatabaseCount('movimientos_materiales', 1);
    }

    public function test_material_inactivo_o_permiso_retirado_no_permiten_operar(): void
    {
        $this->propietario->revokePermissionTo('inventario.ajustes.crear');
        // El permiso heredado del rol se retira del rol en esta base aislada.
        $this->propietario->roles->first()->revokePermissionTo('inventario.ajustes.crear');
        $this->post(route('admin.existencias.store', $this->material), $this->datos('2'))->assertForbidden();
        $this->propietario->roles->first()->givePermissionTo('inventario.ajustes.crear');
        $this->material->activo = false;
        $this->material->save();
        $this->post(route('admin.existencias.store', $this->material), $this->datos('2'))->assertForbidden();
        $this->assertDatabaseCount('movimientos_materiales', 0);
    }

    public function test_error_al_guardar_revierte_la_operacion_y_correccion_sin_cambios_no_crea_evento(): void
    {
        $this->put(route('admin.existencias.update', $this->material), $this->datos('0'))->assertSessionHasErrors('cantidad');
        $this->assertDatabaseCount('movimientos_materiales', 0);
        \App\Models\MovimientoMaterial::creating(function () { throw new \RuntimeException('Fallo de prueba'); });
        try {
            app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material, $this->datos('3'));
            $this->fail('Debió fallar el guardado.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo de prueba', $error->getMessage());
        } finally {
            \App\Models\MovimientoMaterial::flushEventListeners();
        }
        $this->assertDatabaseCount('movimientos_materiales', 0);
        $this->assertDatabaseCount('existencias_materiales', 0);
    }
}
