<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdaptacionEscolarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermisosSeeder::class);
        $this->actingAs(User::factory()->create()->assignRole('propietario'));
    }

    public function test_campos_opcionales_omitidos_se_conservan_y_null_los_limpia(): void
    {
        $datos = ['nombre' => 'Cliente completo', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => '1'];
        $this->post('/admin/clientes/create', $datos + ['email' => 'cliente@example.test', 'direccion' => 'Dirección original', 'observaciones' => 'Observación original'])->assertRedirect();
        $cliente = Cliente::sole();
        $this->put('/admin/clientes/'.$cliente->id, [...$datos, 'telefono' => '71111111'])->assertRedirect();
        $cliente->refresh();
        $this->assertSame('cliente@example.test', $cliente->email);
        $this->assertSame('Dirección original', $cliente->direccion);
        $this->assertSame('Observación original', $cliente->observaciones);
        $this->put('/admin/clientes/'.$cliente->id, [...$datos, 'telefono' => '71111111', 'email' => null])->assertRedirect();
        $this->assertNull($cliente->fresh()->email);
        $this->assertSame('Dirección original', $cliente->fresh()->direccion);
        $evento = $cliente->auditorias()->latest('id')->firstOrFail();
        $this->assertSame('cliente@example.test', $evento->datos_anteriores['email']);
        $this->assertNull($evento->datos_nuevos['email']);
    }

    public function test_categoria_conserva_estado_omitido_y_valida_nombre_normalizado(): void
    {
        $this->post('/admin/categorias/create', ['nombre' => '  Acabados  ', 'activo' => '0'])->assertRedirect();
        $categoria = Categoria::sole();
        $this->assertSame('Acabados', $categoria->nombre);
        $this->assertFalse($categoria->activo);
        $this->postJson('/admin/categorias/create', ['nombre' => ' Acabados '])->assertUnprocessable()->assertJsonValidationErrors('nombre');
        $this->put('/admin/categorias/'.$categoria->id, ['nombre' => ' Acabados actualizados '])->assertRedirect();
        $this->assertFalse($categoria->fresh()->activo);
        $this->assertSame('Acabados actualizados', $categoria->fresh()->nombre);
        $this->assertCount(2, $categoria->auditorias);
    }

    public function test_fallo_de_auditoria_revierte_el_guardado_del_controlador(): void
    {
        $this->withoutExceptionHandling();
        $evento = 'eloquent.creating: '.Auditoria::class;
        Event::listen($evento, function () {
            throw new \RuntimeException('Fallo controlado de auditoría');
        });
        try {
            $this->post('/admin/clientes/create', ['nombre' => 'No debe persistir', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => '1']);
            $this->fail('El fallo de auditoría debía interrumpir la transacción.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo controlado de auditoría', $error->getMessage());
        } finally {
            Event::forget($evento);
        }
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('auditorias', 0);
    }
}
