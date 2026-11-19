<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Material;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CatalogoService;
use App\Services\CotizacionService;
use App\Services\PedidoService;
use App\Services\PermisosUsuarioService;
use App\Services\ReporteService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CotizacionesEstadoTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $maestro;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $categoria = Categoria::create(['nombre' => 'Material principal']);
        $this->material = app(CatalogoService::class)->guardar($this->propietario, ['nombre' => 'Material de estados QA', 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', 'metro')->value('id'), 'precio_referencia' => '40', 'activo' => true]);
        $this->actingAs($this->propietario);
    }

    private function crear(bool $pendiente = false): Cotizacion
    {
        return app(CotizacionService::class)->guardar($this->propietario, [
            'clave_registro' => (string) Str::uuid(), 'fecha' => today()->toDateString(),
            'detalles' => [['tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'), 'material_principal_id' => $this->material->id,
                'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0,
                'acabado' => 'Barnizado natural', 'cantidad_muebles' => 1, 'mano_obra_unitaria' => '20', 'otros_costos_total' => '5',
                'materiales' => [['material_id' => $this->material->id, 'componente' => 'General', 'cantidad_unitaria' => '2',
                    'estado_costo' => $pendiente ? 'pendiente' : 'conocido', 'costo_unitario_estimado' => $pendiente ? null : '40', 'fuente_costo' => 'referencia_catalogo']]]],
        ]);
    }

    private function aprobar(Cotizacion $cotizacion): Cotizacion
    {
        return app(CotizacionService::class)->aprobar($this->propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => [$cotizacion->detalles->first()->id => '150']]);
    }

    private function rechazo(Cotizacion $cotizacion): array
    {
        return ['version' => $cotizacion->version, 'confirmacion' => true, 'motivo' => 'El cliente decidió no aceptar este presupuesto.'];
    }

    public function test_elimina_solo_el_borrador_y_sus_detalles_con_auditoria(): void
    {
        $cotizacion = $this->crear();
        app(\App\Services\ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material, ['clave' => (string) Str::uuid(), 'cantidad' => '5', 'unidad_id' => $this->material->unidad_id, 'existencia_esperada' => '0', 'motivo' => 'Existencia que debe conservarse al borrar el presupuesto.']);
        $this->delete(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertRedirect(route('admin.cotizaciones.index'));
        $this->assertDatabaseCount('cotizaciones', 0);
        $this->assertDatabaseCount('detalles_cotizacion', 0);
        $this->assertDatabaseCount('materiales_cotizacion', 0);
        $this->assertDatabaseCount('materiales', 1);
        $this->assertDatabaseCount('existencias_materiales', 1);
        $this->assertDatabaseCount('movimientos_materiales', 1);
        $this->assertSame('5.0000', $this->material->existencia()->first()->cantidad);
        $evento = Auditoria::where('accion', 'eliminado')->sole();
        $this->assertSame($this->propietario->id, $evento->usuario_id);
        $this->assertCount(1, $evento->datos_anteriores['detalles']);
        $this->deleteJson(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertNotFound();
    }

    public function test_pendiente_puede_eliminarse_pero_no_con_version_vencida(): void
    {
        $cotizacion = $this->crear(true);
        $this->deleteJson(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version + 1])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertDatabaseHas('cotizaciones', ['id' => $cotizacion->id]);
        $this->delete(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertRedirect();
        $this->assertDatabaseCount('cotizaciones', 0);
    }

    public function test_rechazo_conserva_precios_y_bloquea_edicion_aprobacion_pdf_y_pedido(): void
    {
        $cotizacion = $this->aprobar($this->crear());
        $precios = $cotizacion->only(['precio_final', 'costo_total', 'aprobado_at', 'aprobado_por_id']);
        $this->post(route('admin.cotizaciones.rechazar', $cotizacion), $this->rechazo($cotizacion))->assertRedirect();
        $cotizacion->refresh();
        $this->assertSame('rechazada', $cotizacion->estado);
        $this->assertEquals($precios, $cotizacion->only(array_keys($precios)));
        $this->assertDatabaseCount('detalles_cotizacion', 1);
        $this->get(route('admin.cotizaciones.show', $cotizacion))->assertOk()->assertSee('El cliente decidió no aceptar')->assertDontSee('Crear pedido');
        $this->get(route('admin.cotizaciones.index', ['estado' => 'rechazada']))->assertOk()->assertSee('Rechazada');
        $this->get(route('admin.cotizaciones.edit', $cotizacion))->assertForbidden();
        $this->putJson(route('admin.cotizaciones.update', $cotizacion), [])->assertForbidden();
        $this->postJson(route('admin.cotizaciones.aprobar', $cotizacion), [])->assertForbidden();
        $this->get(route('admin.cotizaciones.pdf', $cotizacion))->assertForbidden();
        $this->get(route('admin.pedidos.create', ['cotizacion' => $cotizacion->id]))->assertUnprocessable();
        $cliente = Cliente::factory()->create();
        try {
            app(PedidoService::class)->crear($this->propietario, ['cotizacion_id' => $cotizacion->id, 'cliente_id' => $cliente->id, 'aceptacion' => true, 'clave_registro' => (string) Str::uuid()]);
            $this->fail('Una rechazada no genera un pedido.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('cotizacion_id', $error->errors());
        }
        $this->deleteJson(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertUnprocessable();
        $this->postJson(route('admin.cotizaciones.rechazar', $cotizacion), $this->rechazo($cotizacion))->assertForbidden();
        $this->assertSame(1, $cotizacion->auditorias()->where('accion', 'rechazada')->count());
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_no_elimina_aprobadas_ni_rechaza_cotizaciones_con_pedido(): void
    {
        $cotizacion = $this->aprobar($this->crear());
        $this->deleteJson(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertUnprocessable();
        $pedido = app(PedidoService::class)->crear($this->propietario, ['cotizacion_id' => $cotizacion->id, 'cliente_id' => Cliente::factory()->create()->id, 'aceptacion' => true, 'clave_registro' => (string) Str::uuid()]);
        $this->postJson(route('admin.cotizaciones.rechazar', $cotizacion), $this->rechazo($cotizacion))->assertForbidden();
        $this->deleteJson(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertUnprocessable();
        $this->assertSame('150.00', $pedido->fresh()->precio_total_acordado);
        $this->assertSame('aprobada', $cotizacion->fresh()->estado);
    }

    public function test_rechazo_exige_motivo_version_actual_y_solicitud_sin_campos_extra(): void
    {
        $cotizacion = $this->crear();
        foreach ([['motivo' => '     '], ['confirmacion' => false], ['version' => 999], ['precio_final' => '1']] as $extra) {
            $this->postJson(route('admin.cotizaciones.rechazar', $cotizacion), [...$this->rechazo($cotizacion), ...$extra])->assertUnprocessable();
        }
        $this->assertSame('borrador', $cotizacion->fresh()->estado);
        $this->assertSame(0, $cotizacion->auditorias()->where('accion', 'rechazada')->count());
    }

    public function test_permisos_especificos_se_delegan_y_la_denegacion_individual_prevalece(): void
    {
        $cotizacion = $this->crear();
        $this->actingAs($this->maestro)->deleteJson(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertForbidden();
        $this->postJson(route('admin.cotizaciones.rechazar', $cotizacion), $this->rechazo($cotizacion))->assertForbidden();
        $this->maestro->givePermissionTo(['cotizaciones.eliminar', 'cotizaciones.rechazar']);
        $this->post(route('admin.cotizaciones.rechazar', $cotizacion), $this->rechazo($cotizacion))->assertRedirect();
        $this->assertSame($this->maestro->id, $cotizacion->auditorias()->where('accion', 'rechazada')->sole()->usuario_id);
        $otra = $this->crear();
        app(PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [Permission::findByName('cotizaciones.eliminar')->id => 'denegar']);
        $this->deleteJson(route('admin.cotizaciones.destroy', $otra), ['version' => $otra->version])->assertForbidden();
    }

    public function test_fallo_auditoria_y_dependencias_adicionales_revierten_las_operaciones(): void
    {
        $cotizacion = $this->crear();
        Schema::create('referencias_cotizacion_qa', function ($tabla) {
            $tabla->id();
            $tabla->foreignId('cotizacion_id')->constrained('cotizaciones')->restrictOnDelete();
        });
        DB::table('referencias_cotizacion_qa')->insert(['cotizacion_id' => $cotizacion->id]);
        $this->deleteJson(route('admin.cotizaciones.destroy', $cotizacion), ['version' => $cotizacion->version])->assertUnprocessable();
        $this->assertDatabaseCount('detalles_cotizacion', 1);
        $this->assertDatabaseCount('materiales_cotizacion', 1);
        $this->assertSame(0, $cotizacion->auditorias()->where('accion', 'eliminado')->count());
        $this->mock(AuditoriaService::class)->shouldReceive('registrar')->andThrow(new \RuntimeException('Fallo aislado de auditoría'));
        try {
            app(CotizacionService::class)->rechazar($this->propietario, $cotizacion, $this->rechazo($cotizacion));
            $this->fail('Debe revertir.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo aislado de auditoría', $error->getMessage());
        }
        $this->assertSame('borrador', $cotizacion->fresh()->estado);
        $this->assertSame(1, $cotizacion->fresh()->version);
    }

    public function test_rechazada_no_se_cuenta_como_aprobada_o_pendiente_en_el_reporte(): void
    {
        $cotizacion = $this->aprobar($this->crear());
        app(CotizacionService::class)->rechazar($this->propietario, $cotizacion, $this->rechazo($cotizacion));
        $reporte = app(ReporteService::class)->generar($this->propietario, 'cotizaciones', ['estado' => 'rechazada']);
        $this->assertSame('0,00', $reporte['totales']['Precios aprobados (Bs)']);
        $this->assertSame(0, $reporte['totales']['Sin precio aprobado']);
        $this->assertSame(1, $reporte['totales']['Rechazadas']);
        $this->assertSame('Rechazada', $reporte['filas'][0][2]);
    }

    public function test_catalogos_deshabilitan_eliminar_cuando_existen_relaciones(): void
    {
        $cotizacion = $this->crear();
        $cliente = Cliente::factory()->create();
        $cotizacion->cliente_id = $cliente->id;
        $cotizacion->save();
        foreach ([['admin.clientes.index', 'admin.clientes.destroy', $cliente], ['admin.materiales.index', 'admin.materiales.destroy', $this->material], ['admin.categorias.index', 'admin.categorias.destroy', $this->material->categoriaMaterial]] as [$index, $destroy, $registro]) {
            $this->get(route($index))->assertOk()->assertSee('delete-unavailable')->assertDontSee('action="'.route($destroy, $registro).'"', false);
            $this->deleteJson(route($destroy, $registro))->assertUnprocessable();
        }
    }
}
