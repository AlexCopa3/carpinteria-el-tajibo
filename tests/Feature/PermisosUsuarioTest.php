<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\PermisoUsuario;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\PermisosUsuarioService;
use App\Support\Acceso;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermisosUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;
    private User $maestro;
    private User $otro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermisosSeeder::class);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $this->otro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($this->propietario);
    }

    private function url(?User $usuario = null): string
    {
        return '/admin/usuarios/'.($usuario ?? $this->maestro)->id.'/permisos';
    }

    private function id(string $permiso): int
    {
        return Permission::findByName($permiso, 'web')->id;
    }

    private function decidir(string $permiso, string $decision, ?User $usuario = null): void
    {
        $this->actingAs($this->propietario)->put($this->url($usuario), [
            'reglas' => [$this->id($permiso) => $decision],
        ])->assertRedirect($this->url($usuario));
    }

    public function test_interfaz_incluye_casillas_del_catalogo_y_restablecimiento_del_rol_para_ambos_roles(): void
    {
        $this->get('/admin/usuarios')->assertOk()->assertSee($this->url())->assertDontSee($this->url($this->propietario));
        foreach ([$this->maestro, $this->propietario] as $usuario) {
            $response = $this->get($this->url($usuario))->assertOk();
            foreach (Permission::all() as $permiso) {
                $response->assertSee('data-permiso="'.$permiso->name.'"', false);
            }
            $response->assertSee('Usar permisos del rol')->assertSee('type="checkbox"', false)->assertDontSee('<select', false)->assertDontSee('Acceso actual:');
        }
    }

    public function test_permitir_finanzas_es_individual_auditado_y_no_cambia_roles_claves_ni_otras_cuentas(): void
    {
        $base = Role::findByName('maestro')->permissions()->pluck('name')->sort()->values()->all();
        $hash = $this->maestro->password;
        $this->maestro->givePermissionTo('casos.consultar');
        $this->decidir('pagos.gestionar', 'permitir');
        $usuario = $this->maestro->fresh();
        $this->assertTrue(Acceso::permite($usuario, 'pagos.gestionar'));
        $this->assertFalse(Acceso::permite($this->otro->fresh(), 'pagos.gestionar'));
        $this->assertTrue($usuario->hasDirectPermission('casos.consultar'));
        $this->assertSame($hash, $usuario->password);
        $this->assertTrue($usuario->hasRole('maestro'));
        $this->assertSame($base, Role::findByName('maestro')->permissions()->pluck('name')->sort()->values()->all());
        $evento = $usuario->auditorias()->where('accion', 'permisos_actualizados')->sole();
        $this->assertSame($this->propietario->id, $evento->usuario_id);
        $this->assertSame([], $evento->datos_anteriores['excepciones']);
        $this->assertTrue($evento->datos_nuevos['excepciones'][0]['permitido']);
        $this->decidir('pagos.gestionar', 'permitir');
        $this->assertSame(1, $usuario->auditorias()->where('accion', 'permisos_actualizados')->count());
    }

    public function test_denegar_prevalece_sobre_rol_asignacion_directa_gate_y_middleware(): void
    {
        $this->maestro->givePermissionTo('clientes.consultar');
        $this->decidir('clientes.consultar', 'denegar');
        $usuario = $this->maestro->fresh();
        $this->assertFalse($usuario->hasPermissionTo('clientes.consultar'));
        $this->assertFalse($usuario->can('clientes.consultar'));
        $this->assertFalse(Gate::forUser($usuario)->allows('viewAny', \App\Models\Cliente::class));
        $this->actingAs($usuario)->get('/admin/clientes')->assertForbidden();
        $this->assertTrue($usuario->hasDirectPermission('clientes.consultar'));
        $this->assertTrue(Acceso::permite($this->otro->fresh(), 'clientes.consultar'));
    }

    public function test_heredar_retira_solo_excepcion_y_recupera_permiso_original(): void
    {
        $this->decidir('clientes.consultar', 'denegar');
        $this->decidir('clientes.consultar', 'heredar');
        $this->assertTrue(Acceso::permite($this->maestro->fresh(), 'clientes.consultar'));
        $this->decidir('pagos.gestionar', 'permitir');
        $this->decidir('pagos.gestionar', 'heredar');
        $this->assertFalse(Acceso::permite($this->maestro->fresh(), 'pagos.gestionar'));
        $this->assertDatabaseCount('permisos_usuario', 0);
    }

    public function test_excepcion_persiste_al_cambiar_el_rol_general(): void
    {
        $this->decidir('clientes.consultar', 'denegar');
        $this->decidir('pagos.gestionar', 'permitir');
        Role::findByName('maestro')->syncPermissions(['panel.consultar', 'clientes.consultar', 'pagos.gestionar']);
        $this->assertFalse(Acceso::permite($this->maestro->fresh(), 'clientes.consultar'));
        $this->assertTrue(Acceso::permite($this->maestro->fresh(), 'pagos.gestionar'));
        $this->assertTrue(Acceso::permite($this->otro->fresh(), 'pagos.gestionar'));
    }

    public function test_usuarios_no_se_delega_y_asignaciones_antiguas_no_dan_acceso_al_maestro(): void
    {
        $reservados = Permission::where('name', 'like', 'usuarios.%')->get();
        foreach ($reservados as $permiso) {
            $this->putJson($this->url(), ['reglas' => [$permiso->id => 'permitir']])->assertUnprocessable()->assertJsonValidationErrors('reglas');
            $this->putJson($this->url($this->propietario), ['reglas' => [$permiso->id => 'denegar']])->assertUnprocessable();
        }
        Role::findByName('maestro')->givePermissionTo($reservados);
        $this->maestro->givePermissionTo($reservados);
        foreach ($reservados as $permiso) {
            PermisoUsuario::create(['user_id' => $this->maestro->id, 'permission_id' => $permiso->id, 'permitido' => true]);
            $this->assertFalse(Acceso::permite($this->maestro->fresh(), $permiso->name));
            $this->assertFalse($this->maestro->fresh()->can($permiso->name));
        }
        $this->actingAs($this->maestro->fresh())->get('/admin/usuarios')->assertForbidden();
        $this->get('/admin/permisos')->assertForbidden();
        $this->get($this->url($this->otro))->assertForbidden();
        $this->putJson($this->url($this->otro), ['reglas' => [$this->id('pagos.gestionar') => 'permitir']])->assertForbidden();
        $this->assertDatabaseCount('permisos_usuario', $reservados->count());
    }

    public function test_propietario_conserva_todo_usuarios_aunque_existan_denegaciones_antiguas(): void
    {
        $reservados = Permission::where('name', 'like', 'usuarios.%')->get();
        Role::findByName('propietario')->revokePermissionTo($reservados);
        foreach ($reservados as $permiso) {
            PermisoUsuario::create(['user_id' => $this->propietario->id, 'permission_id' => $permiso->id, 'permitido' => false]);
            $this->assertTrue(Acceso::permite($this->propietario->fresh(), $permiso->name));
        }
        $this->actingAs($this->propietario->fresh())->get('/admin/usuarios')->assertOk();
        $this->get('/admin/permisos')->assertOk();
        $this->assertDatabaseCount('permisos_usuario', $reservados->count());
    }

    public function test_propietario_puede_restringir_sus_operaciones_pero_conserva_recuperacion(): void
    {
        $this->decidir('pagos.gestionar', 'denegar', $this->propietario);
        $this->assertFalse(Acceso::permite($this->propietario->fresh(), 'pagos.gestionar'));
        foreach (config('gestion_permisos.proteccion_propietario') as $permiso) {
            $this->putJson($this->url($this->propietario), ['reglas' => [$this->id($permiso) => 'denegar']])->assertUnprocessable();
            $this->assertTrue(Acceso::permite($this->propietario->fresh(), $permiso));
        }
        $this->get('/admin/permisos')->assertOk();
    }

    public function test_servidor_rechaza_id_estado_guard_y_campos_no_permitidos(): void
    {
        $this->putJson($this->url(), ['reglas' => [999999 => 'permitir']])->assertUnprocessable();
        $otroGuard = Permission::create(['name' => 'prueba.consultar', 'guard_name' => 'api']);
        $this->putJson($this->url(), ['reglas' => [$otroGuard->id => 'permitir']])->assertUnprocessable();
        $this->putJson($this->url(), ['reglas' => [$this->id('pagos.gestionar') => 'inventar']])->assertUnprocessable();
        $this->putJson($this->url(), ['reglas' => [], 'rol' => 'propietario'])->assertUnprocessable()->assertJsonValidationErrors('rol');
        $this->putJson($this->url(), ['permisos' => ['pagos.gestionar']])->assertUnprocessable();
        $this->assertDatabaseCount('permisos_usuario', 0);
        $this->assertSame(0, Auditoria::where('accion', 'permisos_actualizados')->count());
    }

    public function test_cuenta_inactiva_no_recibe_acceso_aunque_tenga_excepcion_positiva(): void
    {
        $this->decidir('pagos.gestionar', 'permitir');
        $this->maestro->activo = false;
        $this->maestro->save();
        $this->assertFalse(Acceso::permite($this->maestro->fresh(), 'pagos.gestionar'));
    }

    public function test_transaccion_revierte_excepciones_si_falla_la_auditoria(): void
    {
        $this->mock(AuditoriaService::class)->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Fallo de prueba'));
        try {
            app(PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [$this->id('pagos.gestionar') => 'permitir']);
            $this->fail('Debía revertir la asignación');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo de prueba', $error->getMessage());
        }
        $this->assertDatabaseCount('permisos_usuario', 0);
        $this->assertFalse(Acceso::permite($this->maestro->fresh(), 'pagos.gestionar'));
    }
}
