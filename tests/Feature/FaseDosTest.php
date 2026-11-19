<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cotizacion;
use App\Models\PermisoUsuario;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FaseDosTest extends TestCase
{
    use RefreshDatabase;

    private string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed(PermisosSeeder::class);
        $this->password = Str::password(24);
    }

    private function cuenta(string $rol = 'propietario'): User
    {
        return User::factory()->create(['password' => $this->password])->assignRole($rol);
    }

    public function test_inicio_correcto_regenera_sesion_y_redirige_al_panel(): void
    {
        $user = $this->cuenta('maestro');
        $this->get('/login')->assertOk();
        $sessionId = session()->getId();
        $this->post('/login', ['email' => strtoupper($user->email), 'password' => $this->password])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->get('/login')->assertRedirect('/admin');
    }

    public function test_credenciales_incorrectas_y_datos_invalidos_no_autentican(): void
    {
        $user = $this->cuenta();
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->postJson('/login', ['email' => ['malicioso'], 'password' => 'incorrecta'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_intentos_repetidos_de_login_se_limitan(): void
    {
        $user = $this->cuenta();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertUnprocessable();
        }
        $this->postJson('/login', ['email' => $user->email, 'password' => $this->password])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_logout_invalida_la_sesion(): void
    {
        $this->actingAs($this->cuenta())->withSession(['dato_privado' => 'temporal']);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull(session('dato_privado'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_rutas_protegidas_y_registro_publico_cerrado(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/usuarios')->assertRedirect(route('login'));
        $this->postJson('/admin/usuarios/create', [])->assertUnauthorized();
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_propietario_crea_maestro_y_la_contrasena_se_guarda_cifrada(): void
    {
        $this->actingAs($this->cuenta());
        $this->post('/admin/usuarios/create', [
            'name' => 'Cuenta de prueba', 'email' => 'MAESTRO@example.test', 'rol' => 'maestro',
            'tipo_identificacion' => 'ci', 'numero_identificacion' => '90001001',
            'is_admin' => true,
        ])->assertRedirect(route('admin.usuarios.index'));
        $nuevo = User::where('email', 'maestro@example.test')->firstOrFail();
        $this->assertTrue($nuevo->hasRole('maestro'));
        $this->assertFalse($nuevo->hasRole('propietario'));
        $this->assertTrue(Hash::check('tj.90001001', $nuevo->password));
        $this->assertTrue($nuevo->password_temporal);
        $this->assertTrue($nuevo->activo);
        $this->assertNotSame($this->password, $nuevo->password);
    }

    public function test_denegacion_individual_prevalece_en_administracion_de_cuentas(): void
    {
        $maestro = $this->cuenta('maestro');
        $otro = $this->cuenta('maestro');
        $maestro->givePermissionTo(['usuarios.gestionar', 'usuarios.crear', 'usuarios.consultar']);
        PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => Permission::findByName('usuarios.gestionar')->id, 'permitido' => false]);
        $maestro->unsetRelation('permisosIndividuales');
        $this->actingAs($maestro);
        $this->get('/admin/usuarios')->assertForbidden();
        $this->get('/admin/usuarios/create')->assertForbidden();
        $this->get('/admin/usuarios/'.$otro->id.'/edit')->assertForbidden();
        $this->postJson('/admin/usuarios/create', ['rol' => 'propietario'])->assertForbidden();
        $this->putJson('/admin/usuarios/'.$otro->id, ['rol' => 'propietario'])->assertForbidden();
        $this->assertSame(2, User::count());
    }

    public function test_campos_y_roles_de_cuentas_se_validan_en_servidor(): void
    {
        $user = $this->cuenta();
        $this->actingAs($user)->postJson('/admin/usuarios/create', [
            'name' => 'Prueba', 'email' => $user->email, 'rol' => 'administrador',
            'password' => 'corta', 'password_confirmation' => 'distinta', 'permissions' => ['cotizaciones.aprobar'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'rol', 'password', 'permissions']);
        $this->assertSame(1, User::count());
    }

    public function test_no_se_crea_otro_propietario_ni_se_retira_el_acceso_propio(): void
    {
        $owner = $this->cuenta();
        $maestro = $this->cuenta('maestro');
        $this->actingAs($owner);
        $this->putJson('/admin/usuarios/'.$maestro->id, [
            'name' => $maestro->name, 'email' => $maestro->email, 'rol' => 'propietario',
        ])->assertUnprocessable()->assertJsonValidationErrors('rol');
        $this->assertTrue($maestro->fresh()->hasRole('maestro'));
        $this->putJson('/admin/usuarios/'.$owner->id, [
            'name' => $owner->name, 'email' => $owner->email, 'rol' => 'maestro',
        ])->assertUnprocessable()->assertJsonValidationErrors('rol');
        $this->assertTrue($owner->fresh()->hasRole('propietario'));
    }

    public function test_panel_maestro_muestra_resumen_operativo_sin_administracion(): void
    {
        $this->actingAs($this->cuenta('maestro'));
        $response = $this->get('/admin')->assertOk()->assertSee('Cotizaciones del mes')
            ->assertDontSee('Materiales sin existencias')->assertDontSee('Próximamente')
            ->assertSee('Cotizaciones recientes')->assertDontSee('Resumen administrativo')
            ->assertDontSee('Pagos')->assertDontSee('Ganancias')->assertDontSee('Usuarios')
            ->assertDontSee('Configuración');
        $this->assertStringNotContainsString('href="http://localhost/cotizaciones"', $response->getContent());
        $this->assertTrue(Schema::hasTable('cotizaciones'));
        $this->assertTrue(Schema::hasTable('pedidos'));
    }

    public function test_panel_propietario_y_vistas_de_usuarios(): void
    {
        $this->actingAs($this->cuenta());
        $this->get('/admin')->assertOk()->assertSee('Tablero de pedidos')->assertSee('Usuarios')->assertSee('Pagos');
        $this->get('/admin/usuarios')->assertOk()->assertSee('Equipo de El Tajibo');
        $this->get('/admin/usuarios/create')->assertOk()->assertSee('Registrar usuario')->assertDontSee('Contraseña inicial');
        $this->get('/admin/usuarios/'.auth()->id().'/edit')->assertOk()->assertSee('Editar cuenta');
    }

    public function test_infraestructura_bloquea_finanzas_y_permiso_de_aprobacion_al_maestro(): void
    {
        foreach (['pagos.consultar', 'ganancias.consultar', 'cotizaciones.aprobar'] as $indice => $permiso) {
            Route::get('/_seguridad/'.$indice, fn () => response()->noContent())->middleware(['web', 'auth', 'permiso:'.$permiso]);
        }
        $maestro = $this->cuenta('maestro');
        $maestro->givePermissionTo(['pagos.consultar', 'ganancias.consultar', 'cotizaciones.aprobar']);
        PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => Permission::findByName('pagos.consultar')->id, 'permitido' => false]);
        PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => Permission::findByName('ganancias.consultar')->id, 'permitido' => false]);
        PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => Permission::findByName('cotizaciones.aprobar')->id, 'permitido' => false]);
        $maestro->unsetRelation('permisosIndividuales');
        $this->actingAs($maestro);
        foreach ([0, 1, 2] as $indice) {
            $this->get('/_seguridad/'.$indice)->assertForbidden();
        }
        $this->assertTrue($maestro->can('costos.consultar'));
        $this->actingAs($this->cuenta());
        foreach ([0, 1, 2] as $indice) {
            $this->get('/_seguridad/'.$indice)->assertNoContent();
        }
    }

    public function test_propietario_inicial_se_crea_sin_credenciales_fijas_y_no_se_puede_repetir(): void
    {
        $this->artisan('tajibo:crear-propietario')
            ->expectsQuestion('Nombre del propietario', 'Propietario de prueba')
            ->expectsQuestion('Correo electrónico', 'propietario@example.test')
            ->expectsQuestion('Contraseña (12 caracteres, mayúscula, minúscula, número y símbolo)', $this->password)
            ->expectsQuestion('Repite la contraseña', $this->password)
            ->assertSuccessful();
        $user = User::where('email', 'propietario@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('propietario'));
        $this->assertTrue(Hash::check($this->password, $user->password));
        $this->artisan('tajibo:crear-propietario')
            ->expectsOutput('Ya existe un propietario. Usa la interfaz protegida para administrar cuentas.')
            ->assertFailed();
        $this->assertSame(1, User::count());
    }

    public function test_login_exige_token_csrf_valido_fuera_del_bypass_de_pruebas(): void
    {
        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        });
        $user = $this->cuenta();
        $this->post('/login', ['email' => $user->email, 'password' => $this->password])->assertStatus(419);
        $this->assertGuest();
        $this->withSession(['_token' => 'token-aislado-de-prueba'])
            ->post('/login', ['email' => $user->email, 'password' => $this->password, '_token' => 'token-aislado-de-prueba'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_validar_precio_final_es_distinto_de_aprobar_y_admite_denegacion_individual(): void
    {
        $cotizacion = new Cotizacion;
        $maestro = $this->cuenta('maestro');
        $maestro->givePermissionTo('cotizaciones.validar');
        PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => Permission::findByName('cotizaciones.validar')->id, 'permitido' => false]);
        $maestro->unsetRelation('permisosIndividuales');
        $this->assertFalse($maestro->can('validatePrice', $cotizacion));
        $owner = $this->cuenta();
        $this->assertTrue($owner->can('validatePrice', $cotizacion));
        Role::findByName('propietario')->revokePermissionTo('cotizaciones.validar');
        $owner = $owner->fresh();
        $this->assertFalse($owner->can('validatePrice', $cotizacion));
        $this->assertTrue($owner->can('approve', $cotizacion));
    }

    public function test_no_se_permite_enviar_contrasena_manual_en_alta(): void
    {
        $this->actingAs($this->cuenta());
        $password = str_repeat('Á', 40).'abc1!';
        $this->postJson('/admin/usuarios/create', [
            'name' => 'Prueba', 'email' => 'prueba-larga@example.test', 'rol' => 'maestro',
            'password' => $password, 'password_confirmation' => $password,
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame(1, User::count());
    }

    public function test_recuperacion_por_correo_desactivada_sin_smtp(): void
    {
        $this->assertFalse(config('autenticacion.recuperacion_habilitada'));
        $this->get('/password/reset')->assertNotFound();
        $this->get('/login')->assertOk()->assertSee('La recuperación por correo aún no está habilitada.');
    }

    public function test_recuperacion_preparada_con_notificaciones_aisladas(): void
    {
        Notification::fake();
        Route::middleware('web')->group(function (): void {
            Auth::routes(['register' => false, 'login' => false, 'logout' => false, 'reset' => true, 'confirm' => false]);
        });
        Route::getRoutes()->refreshNameLookups();
        $user = $this->cuenta('maestro');
        $this->get('/password/reset')->assertOk();
        $this->post('/password/email', ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $nueva = Str::password(24);
        $this->post('/password/reset', [
            'token' => $token, 'email' => $user->email, 'password' => $nueva, 'password_confirmation' => $nueva,
        ])->assertRedirect('/admin');
        $this->assertTrue(Hash::check($nueva, $user->fresh()->password));
        $evento = Auditoria::where('registro_type', User::class)->where('registro_id', $user->id)->where('accion', 'contrasena_restablecida')->sole();
        $this->assertSame($user->id, $evento->usuario_id);
        $this->assertStringNotContainsString($nueva, $evento->toJson());
    }
}
