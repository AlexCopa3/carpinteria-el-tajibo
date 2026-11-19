<?php

namespace Tests\Feature;

use Tests\Fixtures\Cotizaciones\AprobarCotizacionRequest;
use Tests\Fixtures\Cotizaciones\CrearCotizacionRequest;
use Tests\Fixtures\Cotizaciones\ModificarCotizacionRequest;
use Tests\Fixtures\Cotizaciones\CotizacionResource;
use App\Models\Cotizacion;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermisosCotizacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed(PermisosSeeder::class);

        // Rutas exclusivas de pruebas: validan HTTP sin implementar CRUD o persistencia.
        Route::bind('cotizacion', fn (string $id) => $this->cotizacion($id === 'aprobada'));
        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get('/_pruebas/cotizaciones', fn () => response()->json([]))
                ->middleware('can:viewAny,App\Models\Cotizacion');
            Route::get('/_pruebas/cotizaciones/{cotizacion}', fn (Request $request) => (new CotizacionResource($request->route('cotizacion')))->response());
            Route::post('/_pruebas/cotizaciones', fn (CrearCotizacionRequest $request) => response()->json($request->validated()));
            Route::patch('/_pruebas/cotizaciones/{cotizacion}', fn (ModificarCotizacionRequest $request) => response()->json($request->validated()));
            Route::post('/_pruebas/cotizaciones/{cotizacion}/aprobar', fn (AprobarCotizacionRequest $request) => response()->json($request->validated()));
        });
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    private function cotizacion(bool $aprobada = false): Cotizacion
    {
        return (new Cotizacion)->forceFill([
            'id' => 1,
            'costo_materiales' => '100.00', 'costo_mano_obra' => '50.00',
            'otros_costos' => '10.00', 'margen' => '20.00', 'ganancia' => '32.00',
            'precio_final' => $aprobada ? '192.00' : null,
            'aprobado_at' => $aprobada ? '2026-10-27 12:00:00' : null,
        ]);
    }

    public function test_maestro_consulta_costos_sin_recibir_datos_administrativos(): void
    {
        $this->actingAs($this->usuario('maestro'));
        $this->getJson('/_pruebas/cotizaciones')->assertOk();
        $this->getJson('/_pruebas/cotizaciones/1')->assertOk()
            ->assertJsonPath('data.costo_materiales', '100.00')
            ->assertJsonPath('data.costo_mano_obra', '50.00')
            ->assertJsonMissingPath('data.margen')
            ->assertJsonMissingPath('data.ganancia')
            ->assertJsonMissingPath('data.pagos');
    }

    public function test_maestro_crea_y_modifica_estimaciones(): void
    {
        $this->actingAs($this->usuario('maestro'));
        $this->postJson('/_pruebas/cotizaciones', [
            'costo_materiales' => '100.00', 'costo_mano_obra' => '50.00',
        ])->assertOk()->assertJsonPath('costo_mano_obra', '50.00');
        $this->patchJson('/_pruebas/cotizaciones/1', ['costo_mano_obra' => '60.00'])
            ->assertOk()->assertJsonPath('costo_mano_obra', '60.00');
    }

    public function test_maestro_no_aprueba_precio_final_incluso_con_permiso_asignado_por_error(): void
    {
        $maestro = $this->usuario('maestro');
        $this->actingAs($maestro);
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', ['precio_final' => '200.00'])->assertForbidden();
        $maestro->givePermissionTo('cotizaciones.aprobar');
        \App\Models\PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => \Spatie\Permission\Models\Permission::findByName('cotizaciones.aprobar')->id, 'permitido' => false]);
        $maestro->unsetRelation('permisosIndividuales');
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', ['precio_final' => '200.00'])->assertForbidden();
    }

    public function test_propietario_puede_validar_precio_y_ver_margen_y_ganancia(): void
    {
        $this->actingAs($this->usuario('propietario'));
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', ['precio_final' => '200.00'])
            ->assertOk()->assertJsonPath('precio_final', '200.00');
        $this->getJson('/_pruebas/cotizaciones/1')->assertOk()
            ->assertJsonPath('data.margen', '20.00')->assertJsonPath('data.ganancia', '32.00');
    }

    public function test_importes_invalidos_se_rechazan_en_servidor(): void
    {
        $this->actingAs($this->usuario('maestro'));
        $this->postJson('/_pruebas/cotizaciones', [
            'costo_materiales' => '-1', 'costo_mano_obra' => 'texto',
        ])->assertUnprocessable()->assertJsonValidationErrors(['costo_materiales', 'costo_mano_obra']);
        $this->patchJson('/_pruebas/cotizaciones/1', ['costo_mano_obra' => '12.345'])
            ->assertUnprocessable()->assertJsonValidationErrors('costo_mano_obra');
        $this->actingAs($this->usuario('propietario'));
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', ['precio_final' => '-20'])
            ->assertUnprocessable()->assertJsonValidationErrors('precio_final');
    }

    public function test_no_se_puede_inyectar_aprobacion_ni_datos_restringidos_en_edicion(): void
    {
        $this->actingAs($this->usuario('maestro'));
        foreach (['precio_final', 'aprobado_at', 'aprobador_id', 'estado', 'margen', 'ganancia', 'pagos', 'rol'] as $campo) {
            $this->patchJson('/_pruebas/cotizaciones/1', [
                'costo_mano_obra' => '60.00', $campo => '1',
            ])->assertUnprocessable()->assertJsonValidationErrors($campo);
        }
        $this->postJson('/_pruebas/cotizaciones', [
            'costo_materiales' => '100', 'costo_mano_obra' => '50', 'margen' => '20',
        ])->assertUnprocessable()->assertJsonValidationErrors('margen');
    }

    public function test_aprobacion_no_acepta_aprobador_o_costos_manipulados(): void
    {
        $this->actingAs($this->usuario('propietario'));
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', [
            'precio_final' => '200.00', 'aprobador_id' => 99, 'costo_mano_obra' => '0',
        ])->assertUnprocessable()->assertJsonValidationErrors(['aprobador_id', 'costo_mano_obra']);
    }

    public function test_las_estimaciones_aprobadas_no_se_editan_ni_reaprueban(): void
    {
        foreach (['maestro', 'propietario'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $this->patchJson('/_pruebas/cotizaciones/aprobada', ['costo_mano_obra' => '20'])
                ->assertForbidden();
        }
        $this->postJson('/_pruebas/cotizaciones/aprobada/aprobar', ['precio_final' => '300'])
            ->assertForbidden();
    }

    public function test_sin_permisos_no_se_consulta_crea_modifica_o_aprueba(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson('/_pruebas/cotizaciones')->assertForbidden();
        $this->getJson('/_pruebas/cotizaciones/1')->assertForbidden();
        $this->postJson('/_pruebas/cotizaciones', [])->assertForbidden();
        $this->patchJson('/_pruebas/cotizaciones/1', [])->assertForbidden();
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', [])->assertForbidden();
    }

    public function test_permisos_consultar_crear_modificar_son_independientes(): void
    {
        $usuario = User::factory()->create()->givePermissionTo('cotizaciones.consultar');
        $this->actingAs($usuario);
        $this->getJson('/_pruebas/cotizaciones')->assertOk();
        $this->postJson('/_pruebas/cotizaciones', [])->assertForbidden();
        $this->patchJson('/_pruebas/cotizaciones/1', [])->assertForbidden();
        $usuario->givePermissionTo('cotizaciones.crear');
        $this->postJson('/_pruebas/cotizaciones', [
            'costo_materiales' => '100', 'costo_mano_obra' => '50',
        ])->assertOk();
        $this->patchJson('/_pruebas/cotizaciones/1', [])->assertForbidden();
    }

    public function test_maestro_valida_materiales_pero_no_tiene_permisos_administrativos(): void
    {
        $maestro = $this->usuario('maestro');
        $propietario = $this->usuario('propietario');
        $this->assertTrue($maestro->can('materiales.validar'));
        foreach (['pagos.consultar', 'pagos.gestionar', 'ganancias.consultar', 'margenes.consultar', 'margenes.gestionar', 'usuarios.gestionar'] as $permiso) {
            $this->assertFalse($maestro->can($permiso));
            $this->assertTrue($propietario->can($permiso));
        }
    }

    public function test_seeder_es_idempotente_y_no_crea_usuarios_o_casos(): void
    {
        $cantidad = Permission::count();
        $this->seed(PermisosSeeder::class);
        $this->assertSame($cantidad, Permission::count());
        $this->assertSame(2, Role::count());
        $this->assertSame(0, User::count());
    }

    public function test_propietario_sin_permiso_de_aprobacion_no_puede_aprobar(): void
    {
        Role::findByName('propietario', 'web')->revokePermissionTo('cotizaciones.aprobar');
        $this->actingAs($this->usuario('propietario'));
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', ['precio_final' => '200'])
            ->assertForbidden();
    }

    public function test_denegacion_individual_no_expone_margenes_al_maestro(): void
    {
        $maestro = $this->usuario('maestro');
        $maestro->givePermissionTo(['margenes.consultar', 'ganancias.consultar']);
        foreach (['margenes.consultar', 'ganancias.consultar'] as $permiso) {
            \App\Models\PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => Permission::findByName($permiso)->id, 'permitido' => false]);
        }
        $this->actingAs($maestro);
        $this->getJson('/_pruebas/cotizaciones/1')->assertOk()
            ->assertJsonMissingPath('data.margen')->assertJsonMissingPath('data.ganancia');
    }

    public function test_invitados_no_acceden_a_operaciones(): void
    {
        $this->getJson('/_pruebas/cotizaciones')->assertUnauthorized();
        $this->postJson('/_pruebas/cotizaciones', [])->assertUnauthorized();
        $this->patchJson('/_pruebas/cotizaciones/1', [])->assertUnauthorized();
        $this->postJson('/_pruebas/cotizaciones/1/aprobar', [])->assertUnauthorized();
    }
}
