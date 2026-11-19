<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\Auditoria;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AjustesTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermisosSeeder::class);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->actingAs($this->propietario);
        Storage::fake('public');
    }

    private function datos(): array
    {
        return ['nombre' => 'Carpintería de prueba', 'direccion' => 'Dirección de prueba', 'telefono' => '70000000', 'email' => 'negocio@example.test'];
    }

    public function test_propietario_registra_y_actualiza_una_unica_ficha_auditada(): void
    {
        $this->get('/admin/ajustes')->assertOk()->assertViewIs('admin.ajustes.index')->assertSee('Guardar ajustes');
        $this->post('/admin/ajustes/create', $this->datos() + ['id' => 99])->assertRedirect(route('admin.ajustes.index'))->assertSessionHas('swal', ['icon' => 'success', 'title' => '¡Guardado!', 'text' => 'Ajustes guardados correctamente.']);
        $ajuste = Ajuste::sole();
        $this->assertSame('BOB', config('tajibo.moneda.codigo'));
        $this->assertGreaterThan(0, $ajuste->id);
        $this->post('/admin/ajustes/create', [...$this->datos(), 'telefono' => '71111111', 'web' => 'www.ejemplo.test', 'descripcion' => 'Descripción de prueba'])->assertRedirect();
        $this->assertSame(1, Ajuste::count());
        $this->assertSame('71111111', $ajuste->fresh()->telefono);
        $this->assertSame('Bs', config('tajibo.moneda.simbolo'));
        $this->get('/admin/ajustes')->assertOk()->assertSee('Actualizar ajustes')->assertSee('Descripción de prueba');
        $this->get('/admin')->assertOk()->assertSee('Carpintería de prueba');
        $eventos = $ajuste->auditorias()->orderBy('id')->get();
        $this->assertCount(2, $eventos);
        $this->assertSame($this->propietario->id, $eventos[1]->usuario_id);
        $this->assertSame('70000000', $eventos[1]->datos_anteriores['telefono']);
    }

    public function test_denegacion_individual_bloquea_ajustes_aunque_spatie_conceda_el_permiso(): void
    {
        $maestro = User::factory()->create()->assignRole('maestro');
        $maestro->givePermissionTo('configuracion.gestionar');
        \App\Models\PermisoUsuario::create(['user_id' => $maestro->id, 'permission_id' => \Spatie\Permission\Models\Permission::findByName('configuracion.gestionar')->id, 'permitido' => false]);
        $maestro->unsetRelation('permisosIndividuales');
        $this->actingAs($maestro)->get('/admin/ajustes')->assertForbidden();
        $this->postJson('/admin/ajustes/create', $this->datos())->assertForbidden();
        $this->get('/admin')->assertOk()->assertDontSee(route('admin.ajustes.index'));
        $this->assertDatabaseCount('ajustes', 0);
    }

    public function test_valida_los_datos_del_negocio(): void
    {
        $this->postJson('/admin/ajustes/create', [])->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'direccion', 'telefono', 'email']);
        $this->postJson('/admin/ajustes/create', [...$this->datos(), 'email' => 'invalido', 'descripcion' => str_repeat('x', 501)])->assertUnprocessable()->assertJsonValidationErrors(['email', 'descripcion']);
        $this->assertDatabaseCount('ajustes', 0);
    }

    public function test_logo_se_conserva_y_se_sustituye_despues_del_guardado(): void
    {
        $this->post('/admin/ajustes/create', $this->datos() + ['logo' => UploadedFile::fake()->image('logo.png', 20, 20)])->assertRedirect();
        $anterior = Ajuste::sole()->logo;
        Storage::disk('public')->assertExists($anterior);
        $this->post('/admin/ajustes/create', [...$this->datos(), 'telefono' => '71111111'])->assertRedirect();
        $this->assertSame($anterior, Ajuste::sole()->logo);
        $this->post('/admin/ajustes/create', $this->datos() + ['logo' => UploadedFile::fake()->image('nuevo.jpg', 20, 20)])->assertRedirect();
        $nuevo = Ajuste::sole()->logo;
        $this->assertNotSame($anterior, $nuevo);
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($nuevo);
        $this->get('/admin/ajustes/logo')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs(User::factory()->create()->assignRole('maestro'))->get('/admin/ajustes/logo')->assertOk();
        $this->post('/logout');
        $this->get('/admin/ajustes/logo')->assertRedirect(route('login'));
    }

    public function test_svg_grafico_valido_y_rechazo_de_contenido_activo_o_logo_invalido(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect width="20" height="20" fill="#123456"/></svg>';
        $this->post('/admin/ajustes/create', $this->datos() + ['logo' => UploadedFile::fake()->createWithContent('logo.svg', $svg)])->assertRedirect();
        $anterior = Ajuste::sole()->logo;
        foreach (['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>', '<svg xmlns="http://www.w3.org/2000/svg"><use href="https://example.test/logo.svg"/></svg>'] as $contenido) {
            $this->postJson('/admin/ajustes/create', $this->datos() + ['logo' => UploadedFile::fake()->createWithContent('logo.svg', $contenido)])->assertUnprocessable()->assertJsonValidationErrors('logo');
        }
        $this->postJson('/admin/ajustes/create', $this->datos() + ['logo' => UploadedFile::fake()->create('archivo.txt', 10, 'text/plain')])->assertUnprocessable()->assertJsonValidationErrors('logo');
        $this->postJson('/admin/ajustes/create', $this->datos() + ['logo' => UploadedFile::fake()->image('grande.png')->size(2049)])->assertUnprocessable()->assertJsonValidationErrors('logo');
        $this->assertSame($anterior, Ajuste::sole()->logo);
        $this->assertCount(1, Storage::disk('public')->allFiles('logos'));
    }

    public function test_fallo_de_auditoria_conserva_logo_anterior_y_retira_archivo_nuevo(): void
    {
        $this->post('/admin/ajustes/create', $this->datos() + ['logo' => UploadedFile::fake()->image('anterior.png')])->assertRedirect();
        $anterior = Ajuste::sole()->logo;
        $this->withoutExceptionHandling();
        $evento = 'eloquent.creating: '.Auditoria::class;
        Event::listen($evento, function () { throw new \RuntimeException('Fallo controlado'); });
        try {
            $this->post('/admin/ajustes/create', [...$this->datos(), 'nombre' => 'No persistir', 'logo' => UploadedFile::fake()->image('nuevo.png')]);
            $this->fail('La auditoría debía impedir confirmar el guardado.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo controlado', $error->getMessage());
        } finally {
            Event::forget($evento);
        }
        $this->assertSame($anterior, Ajuste::sole()->logo);
        $this->assertSame('Carpintería de prueba', Ajuste::sole()->nombre);
        Storage::disk('public')->assertExists($anterior);
        $this->assertCount(1, Storage::disk('public')->allFiles('logos'));
        $this->assertSame(1, Ajuste::sole()->auditorias()->count());
    }

    public function test_id_se_genera_automaticamente_y_no_se_asume_que_la_ficha_sea_uno(): void
    {
        // Ficha de prueba con otro ID para comprobar que no se reasigna al actualizar.
        \Illuminate\Support\Facades\DB::table('ajustes')->insert(['id' => 41, ...$this->datos()]);
        $this->post('/admin/ajustes/create', [...$this->datos(), 'nombre' => 'Ficha con ID automático', 'logo' => UploadedFile::fake()->image('logo.png')])->assertRedirect();
        $ajuste = Ajuste::sole();
        $this->assertSame(41, $ajuste->id);
        $this->assertSame('Ficha con ID automático', $ajuste->nombre);
        $this->get('/admin')->assertOk()->assertSee('Ficha con ID automático');
        $this->get('/admin/ajustes/logo')->assertOk();
        $this->assertSame(41, $ajuste->auditorias()->sole()->registro_id);

        // Inserción directa, solo en la base aislada de pruebas, para verificar el autoincremento.
        $idGenerado = \Illuminate\Support\Facades\DB::table('ajustes')->insertGetId($this->datos());
        $this->assertGreaterThan(41, $idGenerado);
        $this->assertTrue((new Ajuste())->getIncrementing());
    }
}
