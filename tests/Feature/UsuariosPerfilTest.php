<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\User;
use App\Services\AuditoriaService;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuariosPerfilTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private string $nueva = 'Clave-Personal-Segura-2026!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermisosSeeder::class);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->actingAs($this->propietario);
    }

    private function datos(): array
    {
        return ['name' => 'Persona de prueba', 'email' => 'persona@example.test', 'tipo_identificacion' => 'ci', 'numero_identificacion' => '0090001', 'rol' => 'maestro'];
    }

    private function registrar(): User
    {
        $this->post('/admin/usuarios/create', $this->datos())->assertRedirect();

        return User::where('email', 'persona@example.test')->firstOrFail();
    }

    private function cambio(string $actual = 'tj.0090001'): array
    {
        return ['current_password' => $actual, 'password' => $this->nueva, 'password_confirmation' => $this->nueva];
    }

    public function test_registra_identificacion_y_clave_temporal_cifrada_sin_password_del_formulario(): void
    {
        $this->get('/admin/usuarios/create')->assertOk()->assertDontSee('name="password"', false)->assertDontSee('name="activo"', false);
        $usuario = $this->registrar();
        $this->assertSame('0090001', $usuario->personal?->numero_identificacion);
        $this->assertTrue(Hash::check('tj.0090001', $usuario->password));
        $this->assertTrue($usuario->password_temporal);
        $this->assertTrue($usuario->activo);
        $this->assertNull($usuario->personal?->telefono);
        $this->assertNull($usuario->personal?->direccion);
        $this->assertStringNotContainsString('tj.0090001', $usuario->auditorias()->sole()->toJson());
        $this->assertStringNotContainsString($usuario->password, $usuario->auditorias()->sole()->toJson());
    }

    public function test_requiere_documento_normaliza_y_rechaza_duplicado_e_inyecciones(): void
    {
        $this->postJson('/admin/usuarios/create', ['name' => 'Prueba', 'email' => 'prueba@example.test', 'rol' => 'maestro'])->assertUnprocessable()->assertJsonValidationErrors(['tipo_identificacion', 'numero_identificacion']);
        $datos = [...$this->datos(), 'tipo_identificacion' => 'PASAPORTE', 'numero_identificacion' => ' ab-001 '];
        $this->post('/admin/usuarios/create', $datos)->assertRedirect();
        $usuario = User::where('email', $datos['email'])->sole();
        $this->assertSame('AB-001', $usuario->personal?->numero_identificacion);
        $this->assertTrue(Hash::check('tj.AB-001', $usuario->password));
        $this->postJson('/admin/usuarios/create', [...$datos, 'email' => 'otra@example.test'])->assertUnprocessable()->assertJsonValidationErrors('numero_identificacion');
        $this->postJson('/admin/usuarios/create', [...$this->datos(), 'email' => 'otra@example.test', 'password' => 'manual', 'password_temporal' => false, 'activo' => false])->assertUnprocessable()->assertJsonValidationErrors(['password', 'password_temporal', 'activo']);
    }

    public function test_primer_login_envia_al_perfil_y_no_permite_otras_operaciones(): void
    {
        $usuario = $this->registrar();
        $this->post('/logout');
        $this->post('/login', ['email' => $usuario->email, 'password' => 'tj.0090001'])->assertRedirect(route('admin.perfil.index'));
        $this->get('/admin')->assertRedirect(route('admin.perfil.index'));
        $this->get('/admin/clientes')->assertRedirect(route('admin.perfil.index'));
        $this->getJson('/admin/materiales')->assertForbidden();
        $this->putJson('/admin/perfil', ['name' => 'Otro', 'email' => $usuario->email])->assertForbidden();
        $this->get('/admin/perfil')->assertOk()->assertSee('Cambia tu contraseña inicial')->assertDontSee('name="rol"', false);
    }

    public function test_cambia_clave_exige_actual_y_no_guarda_secretos_en_auditoria(): void
    {
        $usuario = $this->registrar();
        $this->actingAs($usuario);
        $this->putJson('/admin/perfil/password', $this->cambio('incorrecta'))->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertTrue($usuario->fresh()->password_temporal);
        $this->put('/admin/perfil/password', $this->cambio())->assertRedirect(route('admin.perfil.index'));
        $usuario->refresh();
        $this->assertFalse($usuario->password_temporal);
        $this->assertTrue(Hash::check($this->nueva, $usuario->password));
        $this->assertFalse(Hash::check('tj.0090001', $usuario->password));
        $this->get('/admin')->assertOk();
        $evento = $usuario->auditorias()->where('accion', 'contrasena_actualizada')->sole()->toJson();
        foreach (['tj.0090001', $this->nueva, $usuario->password, $usuario->remember_token] as $secreto) {
            $this->assertStringNotContainsString($secreto, $evento);
        }
    }

    public function test_perfil_permite_solo_datos_propios_y_no_roles_o_estado(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        Personal::create(['user_id' => $usuario->id, 'telefono' => '70000000']);
        $this->actingAs($usuario);
        $datos = ['name' => 'Nombre actualizado', 'email' => 'NUEVO@example.test', 'telefono' => '71111111', 'direccion' => 'Dirección de prueba'];
        foreach (['id' => $this->propietario->id, 'rol' => 'propietario', 'roles' => ['propietario'], 'permissions' => ['usuarios.gestionar'], 'activo' => false, 'password_temporal' => false] as $campo => $valor) {
            $this->putJson('/admin/perfil', [...$datos, $campo => $valor])->assertUnprocessable()->assertJsonValidationErrors($campo);
        }
        $this->put('/admin/perfil', $datos)->assertRedirect();
        $this->assertSame('Nombre actualizado', $usuario->fresh()->name);
        $this->assertSame('nuevo@example.test', $usuario->fresh()->email);
        $this->assertSame('71111111', $usuario->fresh()->personal?->telefono);
        $this->assertTrue($usuario->fresh()->hasRole('maestro'));
        $this->get('/admin/usuarios/'.$this->propietario->id)->assertForbidden();
        $this->get('/admin/perfil/'.$this->propietario->id)->assertNotFound();
    }

    public function test_cuentas_previas_conservan_clave_y_no_exigen_cambio_inicial(): void
    {
        $clave = 'Clave-Anterior-123!';
        $usuario = User::factory()->create(['password' => $clave])->assignRole('maestro');
        $hash = $usuario->getRawOriginal('password');
        $this->assertFalse($usuario->password_temporal);
        $this->assertNull($usuario->personal?->numero_identificacion);
        $this->actingAs($usuario)->get('/admin')->assertOk();
        $this->put('/admin/perfil', ['name' => $usuario->name, 'email' => $usuario->email, 'telefono' => '70000000'])->assertRedirect();
        $this->assertSame($hash, $usuario->fresh()->getRawOriginal('password'));
    }

    public function test_propietario_completa_personales_sin_restablecer_contrasena(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        $hash = $usuario->password;
        $this->put('/admin/usuarios/'.$usuario->id, [...$this->datos(), 'email' => $usuario->email, 'telefono' => '70000000', 'direccion' => 'Dirección de prueba', 'activo' => true])->assertRedirect();
        $this->assertSame($hash, $usuario->fresh()->password);
        $this->assertFalse($usuario->fresh()->password_temporal);
        $this->get('/admin/usuarios/'.$usuario->id)->assertSee('0090001')->assertSee('70000000')->assertSee('Dirección de prueba')->assertDontSee($hash);
        $this->get('/admin/usuarios/'.$usuario->id.'/edit')->assertOk()->assertDontSee('name="password"', false);
    }

    public function test_cuenta_inactiva_no_puede_cambiar_perfil_o_clave(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        $this->actingAs($usuario);
        DB::table('users')->where('id', $usuario->id)->update(['activo' => false]);
        $this->putJson('/admin/perfil/password', $this->cambio())->assertForbidden();
        $this->assertGuest();
    }

    public function test_clave_nueva_conserva_reglas_y_no_admite_exceso_de_bytes(): void
    {
        $usuario = $this->registrar();
        $this->actingAs($usuario);
        foreach (['corta', str_repeat('Á', 40).'abc1!'] as $clave) {
            $this->putJson('/admin/perfil/password', ['current_password' => 'tj.0090001', 'password' => $clave, 'password_confirmation' => $clave])->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->assertTrue($usuario->fresh()->password_temporal);
    }

    public function test_auditoria_fallida_revierte_password_y_bandera(): void
    {
        $usuario = $this->registrar();
        $hash = $usuario->password;
        $this->actingAs($usuario);
        $this->mock(AuditoriaService::class)->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Fallo aislado'));
        $this->withoutExceptionHandling();
        try {
            $this->put('/admin/perfil/password', $this->cambio());
            $this->fail('Debía revertirse');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo aislado', $error->getMessage());
        }
        $this->assertSame($hash, $usuario->fresh()->password);
        $this->assertTrue($usuario->fresh()->password_temporal);
    }

    public function test_clave_cambiada_invalida_sesiones_anteriores_del_mismo_usuario(): void
    {
        $usuario = $this->registrar();
        $this->actingAs($usuario);
        foreach (['otro-dispositivo' => $usuario->id, 'otra-persona' => $this->propietario->id] as $id => $userId) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
        }
        config(['session.driver' => 'database']);
        $this->put('/admin/perfil/password', $this->cambio())->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'otro-dispositivo']);
        $this->assertDatabaseHas('sessions', ['id' => 'otra-persona']);
    }

    public function test_ambos_roles_completan_y_modifican_su_documento_sin_cambiar_clave_o_terceros(): void
    {
        foreach (['propietario', 'maestro'] as $indice => $rol) {
            $usuario = User::factory()->create()->assignRole($rol);
            $hash = $usuario->password;
            $this->actingAs($usuario);
            $this->get('/admin/perfil')->assertOk()->assertSee('name="tipo_identificacion"', false)->assertSee('name="numero_identificacion"', false);
            $datos = ['name' => $usuario->name, 'email' => $usuario->email, 'tipo_identificacion' => ' PASAPORTE ', 'numero_identificacion' => ' ab-00'.$indice.' '];
            $this->put('/admin/perfil', $datos)->assertRedirect(route('admin.perfil.index'));
            $personal = $usuario->fresh()->personal;
            $this->assertSame('pasaporte', $personal->tipo_identificacion);
            $this->assertSame('AB-00'.$indice, $personal->numero_identificacion);
            $this->assertSame($hash, $usuario->fresh()->password);
            $this->assertFalse($usuario->fresh()->password_temporal);
            $this->assertTrue($usuario->fresh()->hasRole($rol));
            $evento = $usuario->auditorias()->where('accion', 'modificado')->sole();
            $this->assertSame($usuario->id, $evento->usuario_id);
            $this->assertSame('AB-00'.$indice, $evento->datos_nuevos['numero_identificacion']);
            $datos['tipo_identificacion'] = 'ci';
            $datos['numero_identificacion'] = '0916369'.$indice;
            $this->put('/admin/perfil', $datos)->assertRedirect();
            // Conservar el propio documento no se interpreta como duplicado.
            $this->put('/admin/perfil', $datos)->assertRedirect();
            $this->assertSame($personal->id, $usuario->fresh()->personal->id);
            $this->assertSame('0916369'.$indice, $usuario->fresh()->personal->numero_identificacion);
            $this->assertSame($hash, $usuario->fresh()->password);
            $this->assertNull($this->propietario->fresh()->personal);
        }
    }

    public function test_documento_del_perfil_rechaza_duplicados_pares_incompletos_y_formato_invalido(): void
    {
        $otro = User::factory()->create()->assignRole('maestro');
        Personal::create(['user_id' => $otro->id, 'tipo_identificacion' => 'ci', 'numero_identificacion' => '001234']);
        $usuario = User::factory()->create()->assignRole('maestro');
        $this->actingAs($usuario);
        $datos = ['name' => $usuario->name, 'email' => $usuario->email];
        $this->putJson('/admin/perfil', [...$datos, 'tipo_identificacion' => 'ci', 'numero_identificacion' => '001234'])->assertUnprocessable()->assertJsonValidationErrors('numero_identificacion');
        $this->putJson('/admin/perfil', [...$datos, 'tipo_identificacion' => 'ci'])->assertUnprocessable()->assertJsonValidationErrors('numero_identificacion');
        $this->putJson('/admin/perfil', [...$datos, 'numero_identificacion' => '002345'])->assertUnprocessable()->assertJsonValidationErrors('tipo_identificacion');
        $this->putJson('/admin/perfil', [...$datos, 'tipo_identificacion' => 'otro', 'numero_identificacion' => '002345'])->assertUnprocessable()->assertJsonValidationErrors('tipo_identificacion');
        foreach (['12/345', str_repeat('1', 31)] as $numero) {
            $this->putJson('/admin/perfil', [...$datos, 'tipo_identificacion' => 'ci', 'numero_identificacion' => $numero])->assertUnprocessable()->assertJsonValidationErrors('numero_identificacion');
        }
        $this->assertNull($usuario->fresh()->personal);
        $this->assertSame('001234', $otro->fresh()->personal->numero_identificacion);
        // La unicidad es por tipo y número, como en Usuarios.
        $this->put('/admin/perfil', [...$datos, 'tipo_identificacion' => 'pasaporte', 'numero_identificacion' => '001234'])->assertRedirect();
        $this->assertSame('pasaporte', $usuario->fresh()->personal->tipo_identificacion);
        // Una petición que omite ambos datos conserva el documento existente.
        $this->put('/admin/perfil', $datos)->assertRedirect();
        $this->assertSame('001234', $usuario->fresh()->personal->numero_identificacion);
    }

    public function test_fallo_de_auditoria_revierte_la_modificacion_de_identificacion(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        Personal::create(['user_id' => $usuario->id, 'tipo_identificacion' => 'ci', 'numero_identificacion' => '001234']);
        $this->actingAs($usuario);
        $this->partialMock(AuditoriaService::class)->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Fallo aislado'));
        $this->withoutExceptionHandling();
        try {
            $this->put('/admin/perfil', ['name' => $usuario->name, 'email' => $usuario->email, 'tipo_identificacion' => 'pasaporte', 'numero_identificacion' => 'AB-002']);
            $this->fail('Debía revertirse la identificación');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo aislado', $error->getMessage());
        }
        $this->assertSame('ci', $usuario->fresh()->personal->tipo_identificacion);
        $this->assertSame('001234', $usuario->fresh()->personal->numero_identificacion);
    }

    public function test_oculta_propietario_al_crear_y_rechaza_una_segunda_cuenta_por_http(): void
    {
        $this->get('/admin/usuarios/create')->assertOk()->assertDontSee('value="propietario"', false)->assertSee('value="maestro"', false);
        $this->postJson('/admin/usuarios/create', [...$this->datos(), 'rol' => 'propietario'])->assertUnprocessable()->assertJsonValidationErrors('rol');
        $this->assertDatabaseMissing('users', ['email' => 'persona@example.test']);
        $this->assertDatabaseCount('personal', 0);
        $this->assertSame(1, User::role('propietario')->count());
    }

    public function test_oculta_propietario_al_editar_maestro_y_bloquea_su_promocion(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        $this->get('/admin/usuarios/'.$usuario->id.'/edit')->assertOk()->assertDontSee('value="propietario"', false);
        $this->putJson('/admin/usuarios/'.$usuario->id, ['name' => $usuario->name, 'email' => $usuario->email, 'rol' => 'propietario'])->assertUnprocessable()->assertJsonValidationErrors('rol');
        $this->assertTrue($usuario->fresh()->hasRole('maestro'));
        $this->assertSame(1, User::role('propietario')->count());
    }

    public function test_propietarios_existentes_conservan_su_rol_al_editar_sus_datos(): void
    {
        $otro = User::factory()->create()->assignRole('propietario');
        $this->get('/admin/usuarios/'.$this->propietario->id.'/edit')->assertOk()->assertSee('value="propietario"', false);
        $this->put('/admin/usuarios/'.$this->propietario->id, ['name' => 'Propietario actualizado', 'email' => $this->propietario->email, 'rol' => 'propietario', 'activo' => true])->assertRedirect();
        $this->assertTrue($this->propietario->fresh()->hasRole('propietario'));
        $this->assertTrue($otro->fresh()->hasRole('propietario'));
        $this->assertSame(2, User::role('propietario')->count());
    }

    public function test_servicio_tambien_impide_crear_o_promover_un_segundo_propietario(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        $servicio = app(\App\Services\UsuarioService::class);
        $datos = [...$this->datos(), 'rol' => 'propietario'];
        foreach (['crear', 'modificar'] as $operacion) {
            try {
                if ($operacion === 'crear') {
                    $servicio->crear($this->propietario, $datos);
                } else {
                    $servicio->modificar($this->propietario, $usuario, [...$datos, 'email' => $usuario->email]);
                }
                $this->fail('No debe permitirse otro propietario');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('rol', $error->errors());
            }
        }
        $this->assertDatabaseMissing('users', ['email' => $datos['email']]);
        $this->assertTrue($usuario->fresh()->hasRole('maestro'));
        $this->assertDatabaseCount('personal', 0);
        $this->assertSame(1, User::role('propietario')->count());
    }
}
