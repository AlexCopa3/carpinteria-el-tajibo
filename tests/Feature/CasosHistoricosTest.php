<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\CasoHistorico;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\MaterialPedido;
use App\Models\Pedido;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CatalogoService;
use App\Services\CierrePedidoService;
use App\Services\CotizacionService;
use App\Services\ExistenciaMaterialService;
use App\Services\FabricacionService;
use App\Services\PedidoService;
use App\Services\RetencionCasoService;
use App\Services\RevisionCasoService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CasosHistoricosTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $maestro;

    private Pedido $pedido;

    private Material $material;

    private Material $adicional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $categoria = Categoria::create(['nombre' => 'Material principal']);
        $catalogo = app(CatalogoService::class);
        $this->material = $catalogo->guardar($this->propietario, ['nombre' => 'Material fraccionable QA', 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', 'metro')->value('id'), 'precio_referencia' => '40', 'activo' => true]);
        $this->adicional = $catalogo->guardar($this->propietario, ['nombre' => 'Material entero QA', 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', 'pieza')->value('id'), 'precio_referencia' => '12', 'activo' => true]);
        app(ExistenciaMaterialService::class)->guardarCantidad($this->propietario, $this->material, ['clave' => (string) Str::uuid(), 'cantidad' => '20', 'unidad_id' => $this->material->unidad_id, 'existencia_esperada' => '0', 'motivo' => 'Saldo de prueba aislado']);
        $detalle = ['tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'), 'material_principal_id' => $this->material->id, 'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural', 'cantidad_muebles' => 2, 'mano_obra_unitaria' => '20', 'otros_costos_total' => '5', 'materiales' => [['material_id' => $this->material->id, 'componente' => 'General', 'cantidad_unitaria' => '3', 'costo_unitario_estimado' => '40', 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo']]];
        $otro = $detalle;
        $otro['cantidad_muebles'] = 1;
        $cotizaciones = app(CotizacionService::class);
        $cotizacion = $cotizaciones->guardar($this->propietario, ['clave_registro' => (string) Str::uuid(), 'fecha' => today()->toDateString(), 'detalles' => [$detalle, $otro]]);
        $cotizacion = $cotizaciones->aprobar($this->propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => $cotizacion->detalles->mapWithKeys(fn ($detalle) => [$detalle->id => '300'])->all()]);
        $cliente = Cliente::create(['nombre' => 'Cliente aislado 4C', 'tipo' => 'persona', 'telefono' => '70000000']);
        $pedidos = app(PedidoService::class);
        $this->pedido = $pedidos->crear($this->propietario, ['cotizacion_id' => $cotizacion->id, 'clave_registro' => (string) Str::uuid(), 'aceptacion' => true, 'cliente_id' => $cliente->id]);
        $this->pedido = $pedidos->cambiarEstado($this->maestro, $this->pedido, ['version' => $this->pedido->version, 'estado' => 'en_fabricacion', 'clave_operacion' => (string) Str::uuid()]);
        $this->actingAs($this->maestro);
    }

    private function terminar(array $cambios = []): void
    {
        $filas = MaterialPedido::orderBy('id')->get()->map(fn ($fila) => [...$fila->only(['id', 'detalle_pedido_id', 'material_id']), 'cantidad_utilizada' => $fila->cantidad_planificada])->all();
        foreach ($cambios as $indice => $valor) {
            $filas[$indice]['cantidad_utilizada'] = $valor;
        }
        app(FabricacionService::class)->guardar($this->maestro, $this->pedido, ['version' => $this->pedido->fresh()->version, 'materiales' => $filas]);
        $this->aceptarCambiosPendientes();
        app(CierrePedidoService::class)->terminar($this->propietario, $this->pedido, ['version' => $this->pedido->fresh()->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true, 'confirmar_precio' => true, 'validar_materiales' => true]);
    }

    private function aceptarCambiosPendientes(): void
    {
        $cotizacion = $this->pedido->fresh()->cotizacion;
        if ($this->pedido->fresh()->cotizacion_aceptada_version === $cotizacion->version) return;
        $precios = [];
        foreach ($cotizacion->detalles as $detalle) {
            $precios[$detalle->id] = $this->pedido->detalles()->where('detalle_cotizacion_id', $detalle->id)->firstOrFail()->precio_acordado;
        }
        $cotizacion = app(CotizacionService::class)->aprobar($this->propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => $precios]);
        app(PedidoService::class)->aceptarCotizacion($this->propietario, $this->pedido, ['version' => $this->pedido->fresh()->version, 'cotizacion_version' => $cotizacion->version, 'aceptacion' => true]);
    }

    private function revisar(CasoHistorico $caso, string $accion, array $extras = [])
    {
        return $this->postJson(route('admin.casos.revisar', $caso), ['version' => $caso->fresh()->version, 'accion' => $accion, 'confirmacion' => true, ...$extras]);
    }

    public function test_retenido_por_detalle_con_cantidades_y_costos_sin_segunda_multiplicacion(): void
    {
        $this->terminar([0 => '5.5']);
        $this->assertDatabaseCount('casos_historicos', 2);
        $this->assertDatabaseCount('materiales_caso', 2);
        $caso = CasoHistorico::orderBy('id')->first();
        $this->assertSame(2, $caso->cantidad_muebles);
        $this->assertSame('5.5000', $caso->materiales->first()->cantidad_total);
        $this->assertSame('220.00', $caso->costo_materiales_estimado_total);
        $this->assertSame('265.00', $caso->costo_estimado_total);
        $this->assertSame('265.00', $caso->costo_presupuestado_total);
        $this->assertSame('300.00', $caso->precio_historico_total);
        $this->assertSame('real', $caso->origen);
        $this->assertSame('revisado', $caso->estado_revision);
        $this->assertSame('estimacion_cotizacion', $caso->fuente_costos);
        $this->assertSame('cotizacion_acordada', $caso->fuente_caracteristicas);
        $this->assertSame('20.0000', $this->material->existencia->cantidad);
        $this->assertSame('600.00', $this->pedido->fresh()->precio_total_acordado);
    }

    public function test_ficha_muestra_unidad_costos_y_precio_sin_datos_personales_o_ganancia(): void
    {
        $this->terminar();
        $caso = CasoHistorico::first();
        $this->actingAs($this->maestro)->get(route('admin.casos.index'))->assertOk()->assertSee('Casos históricos');
        $this->get(route('admin.casos.show', $caso))->assertOk()->assertSee('Metro')->assertSee('Cantidad total')->assertSee('Costos y precio del grupo')->assertSee('285,00')->assertSee('300,00')->assertDontSee('Cliente aislado 4C')->assertDontSee('recargo_porcentaje');
    }

    public function test_todos_los_lotes_simulados_conservan_origen_y_no_inventan_revision_humana(): void
    {
        foreach (['datos_bolivianos_20261105_v1', 'datos_bolivianos_20261105_v2'] as $lote) {
            app(AuditoriaService::class)->registrar(null, $this->pedido, 'datos_ejemplo_cargados', [], ['origen' => 'simulado', 'lote' => $lote, 'ids' => ['pedidos' => [$this->pedido->id]]]);
        }
        $this->terminar();
        foreach (CasoHistorico::all() as $caso) {
            $this->assertSame('simulado', $caso->origen);
            $this->assertSame('pendiente', $caso->estado_revision);
            $this->assertNull($caso->materiales_revisados_at);
            $this->assertNull($caso->precio_revisado_por_id);
        }
    }

    public function test_material_adicional_con_catalogo_conocido_no_inventa_costo_historico(): void
    {
        $this->maestro->roles->first()->revokePermissionTo('cotizaciones.modificar');
        $filas = MaterialPedido::all()->map(fn ($fila) => [...$fila->only(['id', 'detalle_pedido_id', 'material_id']), 'cantidad_utilizada' => $fila->cantidad_planificada])->all();
        $filas[] = ['detalle_pedido_id' => $filas[0]['detalle_pedido_id'], 'material_id' => $this->adicional->id, 'cantidad_utilizada' => '2'];
        app(FabricacionService::class)->guardar($this->maestro, $this->pedido, ['version' => $this->pedido->fresh()->version, 'materiales' => $filas]);
        app(CierrePedidoService::class)->terminar($this->propietario, $this->pedido, ['version' => $this->pedido->fresh()->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true, 'confirmar_precio' => true, 'validar_materiales' => true]);
        $caso = CasoHistorico::first();
        $material = $caso->materiales()->where('material_id', $this->adicional->id)->first();
        $this->assertNull($material->costo_unitario_estimado);
        $this->assertNull($material->subtotal_estimado);
        $this->assertNull($caso->costo_estimado_total);
        $this->assertStringContainsString('sin costo estimado histórico', $caso->motivo_no_elegible);
        $this->actingAs($this->propietario);
        $this->revisar($caso, 'precio')->assertUnprocessable()->assertJsonValidationErrors('caso');
        $this->assertSame('terminado', $this->pedido->fresh()->estado);
    }

    public function test_cero_explicitado_se_retiene_sin_recomendar_mueble_sin_materiales(): void
    {
        $this->terminar([0 => '0', 1 => '0']);
        $caso = CasoHistorico::first();
        $this->assertSame('0.0000', $caso->materiales->first()->cantidad_total);
        $this->assertStringContainsString('No hay materiales', $caso->motivo_no_elegible);
        $this->assertSame('pendiente', $caso->estado_revision);
    }

    public function test_reenvio_entrega_y_conciliacion_no_duplican_casos(): void
    {
        $this->terminar();
        $antes = DB::table('casos_historicos')->get()->toJson();
        $auditorias = Auditoria::where('accion', 'caso_retenido')->count();
        app(RetencionCasoService::class)->retener($this->pedido);
        app(CierrePedidoService::class)->entregar($this->propietario, $this->pedido, ['version' => $this->pedido->fresh()->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true]);
        $this->artisan('tajibo:conciliar-casos', ['--aplicar' => true])->assertSuccessful();
        $this->assertSame($antes, DB::table('casos_historicos')->get()->toJson());
        $this->assertSame($auditorias, Auditoria::where('accion', 'caso_retenido')->count());
    }

    public function test_conciliacion_recupera_terminado_previo_sin_reescribir_pedido(): void
    {
        $this->terminar();
        DB::table('materiales_caso')->delete();
        DB::table('casos_historicos')->delete();
        $antes = $this->pedido->fresh()->getAttributes();
        $this->artisan('tajibo:conciliar-casos')->assertSuccessful();
        $this->assertDatabaseCount('casos_historicos', 0);
        $this->artisan('tajibo:conciliar-casos', ['--aplicar' => true])->assertSuccessful();
        $this->assertDatabaseCount('casos_historicos', 2);
        $this->assertSame($antes, $this->pedido->fresh()->getAttributes());
    }

    public function test_revisiones_separadas_no_convierten_demostracion_en_real(): void
    {
        $caso = CasoHistorico::factory()->create();
        $this->actingAs($this->maestro);
        $this->revisar($caso, 'materiales')->assertRedirect();
        $this->assertSame('pendiente', $caso->fresh()->estado_revision);
        $this->revisar($caso, 'precio')->assertForbidden();
        $this->maestro->givePermissionTo('casos.validar_precios');
        \App\Models\PermisoUsuario::create(['user_id' => $this->maestro->id, 'permission_id' => \Spatie\Permission\Models\Permission::findByName('casos.validar_precios')->id, 'permitido' => false]);
        $this->maestro->unsetRelation('permisosIndividuales');
        $this->revisar($caso, 'precio')->assertForbidden();
        $this->actingAs($this->propietario);
        $this->revisar($caso, 'precio')->assertRedirect();
        $this->assertSame('revisado', $caso->fresh()->estado_revision);
        $this->assertSame('simulado', $caso->fresh()->origen);
        $this->assertSame($this->maestro->id, $caso->fresh()->materiales_revisados_por_id);
        $this->assertSame($this->propietario->id, $caso->fresh()->precio_revisado_por_id);
    }

    public function test_campos_privilegiados_version_y_confirmacion_protegidos(): void
    {
        $caso = CasoHistorico::factory()->create();
        $this->actingAs($this->propietario);
        foreach (['origen' => 'real', 'precio_historico_total' => '1', 'materiales_revisados_por_id' => $this->maestro->id, 'fuente_costos' => 'real'] as $campo => $valor) {
            $this->revisar($caso, 'materiales', [$campo => $valor])->assertUnprocessable()->assertJsonValidationErrors('solicitud');
        }
        $this->revisar($caso, 'materiales', ['confirmacion' => false])->assertUnprocessable();
        $version = $caso->version;
        $this->revisar($caso, 'materiales')->assertRedirect();
        $this->revisar($caso, 'precio', ['version' => $version])->assertUnprocessable()->assertJsonValidationErrors('version');
    }

    public function test_descarte_con_motivo_conserva_datos_y_no_se_revalida(): void
    {
        $caso = CasoHistorico::factory()->create();
        $this->actingAs($this->propietario);
        $this->revisar($caso, 'descartar', ['observaciones' => '     '])->assertUnprocessable();
        $this->revisar($caso, 'descartar', ['observaciones' => 'Referencia que requiere revisión técnica'])->assertRedirect();
        $this->assertSame('descartado', $caso->fresh()->estado_revision);
        $this->assertDatabaseCount('materiales_caso', 1);
        $this->revisar($caso, 'materiales')->assertUnprocessable();
    }

    public function test_instantaneas_no_cambian_con_catalogo_y_no_se_editan_o_borran(): void
    {
        $this->terminar();
        $caso = CasoHistorico::first();
        DB::table('materiales')->where('id', $this->material->id)->update(['precio_referencia' => '999']);
        $this->assertSame('40.000000', $caso->materiales->first()->costo_unitario_estimado);
        try {
            $caso->precio_historico_total = '1';
            $caso->save();
            $this->fail('No debe editar precio histórico.');
        } catch (\LogicException $error) {
            $this->assertStringContainsString('instantánea', $error->getMessage());
        }
        $this->expectException(\LogicException::class);
        $caso->materiales->first()->delete();
    }

    public function test_permisos_consulta_costos_y_actor_inactivo(): void
    {
        $caso = CasoHistorico::factory()->create();
        $this->actingAs($this->maestro);
        $rol = $this->maestro->roles->first();
        $rol->revokePermissionTo('costos.consultar');
        $this->get(route('admin.casos.show', $caso))->assertOk()->assertDontSee('70,00')->assertSee('100,00');
        $rol->revokePermissionTo('casos.consultar');
        $this->get(route('admin.casos.index'))->assertForbidden();
        $rol->givePermissionTo('casos.consultar');
        $this->maestro->activo = false;
        $this->maestro->save();
        $this->expectException(AuthorizationException::class);
        app(RevisionCasoService::class)->revisar($this->maestro, $caso, ['version' => $caso->version, 'accion' => 'materiales', 'confirmacion' => true]);
    }

    public function test_fallo_de_retencion_revierte_cierre_casos_y_eventos(): void
    {
        $filas = MaterialPedido::all()->map(fn ($fila) => [...$fila->only(['id', 'detalle_pedido_id', 'material_id']), 'cantidad_utilizada' => '1'])->all();
        app(FabricacionService::class)->guardar($this->maestro, $this->pedido, ['version' => $this->pedido->fresh()->version, 'materiales' => $filas]);
        $this->aceptarCambiosPendientes();
        $version = $this->pedido->fresh()->version;
        $auditoria = Mockery::mock(AuditoriaService::class)->makePartial();
        $auditoria->shouldReceive('registrar')->withArgs(fn ($actor, $registro, $accion) => $accion === 'caso_retenido')->once()->andThrow(new \RuntimeException('Retención fallida de prueba'));
        $this->app->instance(AuditoriaService::class, $auditoria);
        try {
            app(CierrePedidoService::class)->terminar($this->propietario, $this->pedido, ['version' => $version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true, 'confirmar_precio' => true, 'validar_materiales' => true]);
            $this->fail('Debe revertir.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Retención fallida de prueba', $error->getMessage());
        }
        $this->assertDatabaseCount('casos_historicos', 0);
        $this->assertDatabaseCount('materiales_caso', 0);
        $this->assertSame('en_fabricacion', $this->pedido->fresh()->estado);
        $this->assertSame($version, $this->pedido->fresh()->version);
    }
}
