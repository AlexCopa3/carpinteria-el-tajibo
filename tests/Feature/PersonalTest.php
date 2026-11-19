<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\User;
use App\Services\AuditoriaService;
use Database\Seeders\PermisosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonalTest extends TestCase
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

    public function test_acceso_y_personales_estan_separados_con_relacion_uno_a_uno(): void
    {
        foreach (['tipo_identificacion', 'numero_identificacion', 'telefono', 'direccion'] as $campo) {
            $this->assertFalse(Schema::hasColumn('users', $campo));
            $this->assertTrue(Schema::hasColumn('personal', $campo));
        }
        foreach (['name', 'email', 'password', 'activo', 'password_temporal'] as $campo) {
            $this->assertTrue(Schema::hasColumn('users', $campo));
            $this->assertFalse(Schema::hasColumn('personal', $campo));
        }
        $this->post('/admin/usuarios/create', ['name' => 'Persona de prueba', 'email' => 'persona@example.test', 'rol' => 'maestro', 'tipo_identificacion' => 'ci', 'numero_identificacion' => '009001'])->assertRedirect();
        $usuario = User::where('email', 'persona@example.test')->sole();
        $this->assertSame('009001', $usuario->personal->numero_identificacion);
        $this->assertSame($usuario->id, $usuario->personal->user->id);
        $this->assertSame(1, Personal::where('user_id', $usuario->id)->count());
        $this->assertArrayNotHasKey('numero_identificacion', $usuario->getAttributes());
    }

    public function test_si_falla_personal_no_queda_una_cuenta_huerfana(): void
    {
        Event::listen('eloquent.creating: '.Personal::class, function () {
            throw new \RuntimeException('Fallo aislado de ficha');
        });
        $this->withoutExceptionHandling();
        try {
            $this->post('/admin/usuarios/create', ['name' => 'Persona fallida', 'email' => 'fallo@example.test', 'rol' => 'maestro', 'tipo_identificacion' => 'ci', 'numero_identificacion' => '009002']);
            $this->fail('Se esperaba error');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo aislado de ficha', $error->getMessage());
        } finally {
            Personal::flushEventListeners();
        }
        $this->assertDatabaseMissing('users', ['email' => 'fallo@example.test']);
        $this->assertDatabaseCount('personal', 0);
        $this->assertDatabaseCount('model_has_roles', 1);
    }

    public function test_actualizar_documento_usa_id_de_personal_y_no_id_de_usuario(): void
    {
        $otro = User::factory()->create()->assignRole('maestro');
        $usuario = User::factory()->create()->assignRole('maestro');
        $personal = Personal::create(['user_id' => $usuario->id, 'tipo_identificacion' => 'ci', 'numero_identificacion' => '009003']);
        Personal::create(['user_id' => $otro->id, 'tipo_identificacion' => 'ci', 'numero_identificacion' => '009004']);
        $this->assertNotSame($usuario->id, $personal->id);
        $datos = ['name' => $usuario->name, 'email' => $usuario->email, 'rol' => 'maestro', 'tipo_identificacion' => 'ci', 'numero_identificacion' => '009003'];
        $this->put('/admin/usuarios/'.$usuario->id, $datos)->assertRedirect();
        $this->assertSame($personal->id, $usuario->fresh()->personal->id);
        $this->putJson('/admin/usuarios/'.$usuario->id, [...$datos, 'numero_identificacion' => '009004'])->assertUnprocessable()->assertJsonValidationErrors('numero_identificacion');
        $this->assertSame(2, Personal::count());
    }

    public function test_cambio_de_contacto_se_guarda_en_personal_y_conserva_auditoria_del_usuario(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        Personal::create(['user_id' => $usuario->id, 'telefono' => '70000000']);
        $hash = $usuario->password;
        $this->actingAs($usuario)->put('/admin/perfil', ['name' => $usuario->name, 'email' => $usuario->email, 'telefono' => '71111111', 'direccion' => 'Dirección de prueba'])->assertRedirect();
        $this->assertSame('71111111', $usuario->fresh()->personal->telefono);
        $this->assertSame($hash, $usuario->fresh()->password);
        $evento = $usuario->auditorias()->where('accion', 'modificado')->sole();
        $this->assertSame('70000000', $evento->datos_anteriores['telefono']);
        $this->assertSame('71111111', $evento->datos_nuevos['telefono']);
    }

    public function test_fallo_de_auditoria_revierte_nombre_y_contacto_en_ambas_tablas(): void
    {
        $usuario = User::factory()->create()->assignRole('maestro');
        Personal::create(['user_id' => $usuario->id, 'telefono' => '70000000']);
        $nombre = $usuario->name;
        $this->actingAs($usuario);
        $this->partialMock(AuditoriaService::class)->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Fallo aislado'));
        $this->withoutExceptionHandling();
        try {
            $this->put('/admin/perfil', ['name' => 'Cambio fallido', 'email' => $usuario->email, 'telefono' => '71111111']);
            $this->fail('Se esperaba reversión');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo aislado', $error->getMessage());
        }
        $this->assertSame($nombre, $usuario->fresh()->name);
        $this->assertSame('70000000', $usuario->fresh()->personal->telefono);
    }

    public function test_no_se_admiten_dos_fichas_para_un_usuario(): void
    {
        Personal::create(['user_id' => $this->propietario->id]);
        $this->expectException(QueryException::class);
        Personal::create(['user_id' => $this->propietario->id]);
    }
}
