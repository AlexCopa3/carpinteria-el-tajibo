<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\User;
use App\Support\Acceso;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermisosRolesTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermisosSeeder::class);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->actingAs($this->propietario);
    }

    public function test_propietario_edita_otros_modulos_con_auditoria_y_sin_reponerlos_al_sembrar(): void
    {
        $rol = Role::findByName('maestro', 'web');
        $maestro = User::factory()->create()->assignRole('maestro');
        $this->get('/admin/permisos/'.$rol->id.'/edit')->assertOk()->assertSee('cotizaciones.aprobar')->assertSee('grupo Usuarios está bloqueado');
        $permisos = ['panel.consultar', 'cotizaciones.aprobar', 'pagos.gestionar'];
        $this->put('/admin/permisos/'.$rol->id, ['permisos' => $permisos])->assertRedirect();
        $this->assertTrue($maestro->fresh()->hasPermissionTo('cotizaciones.aprobar'));
        $this->assertFalse($maestro->fresh()->hasPermissionTo('costos.consultar'));
        $this->seed(PermisosSeeder::class);
        $this->assertFalse($maestro->fresh()->hasPermissionTo('costos.consultar'));
        $evento = Auditoria::where('accion', 'permisos_actualizados')->sole();
        $this->assertSame($this->propietario->id, $evento->usuario_id);
        $this->assertContains('costos.consultar', $evento->datos_anteriores['permisos']);
        $this->assertContains('pagos.gestionar', $evento->datos_nuevos['permisos']);
    }

    public function test_es_posible_desmarcar_todos_los_permisos_del_rol(): void
    {
        $rol = Role::findByName('maestro', 'web');
        $this->put('/admin/permisos/'.$rol->id, [])->assertRedirect();
        $this->assertSame(0, $rol->permissions()->count());
    }

    public function test_rechaza_permisos_inexistentes_duplicados_y_roles_ajenos(): void
    {
        $rol = Role::findByName('maestro', 'web');
        foreach ([['inexistente.consultar'], ['pagos.gestionar', 'pagos.gestionar']] as $permisos) {
            $this->putJson('/admin/permisos/'.$rol->id, ['permisos' => $permisos])->assertUnprocessable();
        }
        $ajeno = Role::create(['name' => 'otro', 'guard_name' => 'web']);
        $this->putJson('/admin/permisos/'.$ajeno->id, ['permisos' => []])->assertForbidden();
        $this->assertSame(0, Auditoria::where('accion', 'permisos_actualizados')->count());
    }

    public function test_propietario_conserva_recuperacion_incluso_con_rol_sin_permisos(): void
    {
        $rol = Role::findByName('propietario', 'web');
        $fijos = \Spatie\Permission\Models\Permission::where('name', 'like', 'usuarios.%')->pluck('name')->all();
        $this->put('/admin/permisos/'.$rol->id, ['permisos' => $fijos])->assertRedirect();
        $this->assertSame(count($fijos), $rol->permissions()->count());
        foreach (config('gestion_permisos.proteccion_propietario') as $permiso) {
            $this->assertTrue(Acceso::permite($this->propietario->fresh(), $permiso));
        }
        $this->assertFalse(Acceso::permite($this->propietario->fresh(), 'pagos.gestionar'));
        $this->get('/admin/permisos')->assertOk();
    }

    public function test_administracion_de_roles_requiere_todos_los_permisos_de_acceso(): void
    {
        $maestro = User::factory()->create()->assignRole('maestro');
        $maestro->givePermissionTo(['usuarios.asignar_roles', 'usuarios.gestionar']);
        $this->actingAs($maestro->fresh())->get('/admin/permisos')->assertForbidden();
        $maestro->givePermissionTo('usuarios.consultar');
        $this->actingAs($maestro->fresh())->get('/admin/permisos')->assertForbidden();
        $this->get('/admin/usuarios')->assertForbidden();
    }

    public function test_servidor_rechaza_conceder_usuarios_al_maestro_y_retirarlos_del_propietario(): void
    {
        $maestro = Role::findByName('maestro', 'web');
        $propietario = Role::findByName('propietario', 'web');
        $reservados = \Spatie\Permission\Models\Permission::where('name', 'like', 'usuarios.%')->pluck('name')->all();
        foreach ($reservados as $permiso) {
            $this->putJson('/admin/permisos/'.$maestro->id, ['permisos' => [...config('permisos.maestro'), $permiso]])->assertUnprocessable()->assertJsonValidationErrors('permisos');
            $sinReservado = array_values(array_diff(config('permisos.propietario'), [$permiso]));
            $this->putJson('/admin/permisos/'.$propietario->id, ['permisos' => $sinReservado])->assertUnprocessable()->assertJsonValidationErrors('permisos');
        }
        $this->assertSame(0, Auditoria::where('accion', 'permisos_actualizados')->count());
        $this->assertSame(15, $maestro->permissions()->count());
        $this->assertSame(50, $propietario->permissions()->count());
    }

}
