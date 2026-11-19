<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstructuraEscolarTest extends TestCase
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

    public function test_alta_y_edicion_usan_rutas_admin_y_vistas_separadas(): void
    {
        $this->get('/admin/clientes/create')->assertOk()->assertViewIs('admin.clientes.create')->assertSee('btn-primary', false);
        $this->post('/admin/clientes/create', ['nombre' => 'Cliente de prueba', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => '1'])
            ->assertRedirect(route('admin.clientes.index'))
            ->assertSessionHas('mensaje', 'Cliente registrado.')
            ->assertSessionHas('icono', 'success');
        $cliente = Cliente::sole();
        $this->get('/admin/clientes/'.$cliente->id.'/edit')->assertOk()->assertViewIs('admin.clientes.edit')->assertSee('Actualizar cliente')->assertSee('btn-success', false);
        $this->get('/clientes')->assertNotFound();
    }

    public function test_alerta_sweetalert_codifica_texto_y_carga_recursos_locales(): void
    {
        $texto = '</script><script>window.inyeccion = true</script>';
        $this->withSession(['mensaje' => $texto, 'icono' => 'success'])->get('/admin')
            ->assertOk()->assertViewIs('admin.index')
            ->assertSee('Swal.fire(', false)
            ->assertSee('vendor/sweetalert2/sweetalert2.all.min.js', false)
            ->assertSee('\\u003C', false)
            ->assertDontSee($texto, false);
    }
}
