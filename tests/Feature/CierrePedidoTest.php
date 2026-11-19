<?php

namespace Tests\Feature;

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
use App\Services\PlanificacionMaterialService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CierrePedidoTest extends TestCase
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

    private function usoCompleto(): void
    {
        $filas = MaterialPedido::orderBy('id')->get()->map(fn ($fila) => [...$fila->only(['id', 'detalle_pedido_id', 'material_id']), 'cantidad_utilizada' => $fila->cantidad_planificada])->all();
        app(FabricacionService::class)->guardar($this->maestro, $this->pedido, ['version' => $this->pedido->fresh()->version, 'materiales' => $filas]);
    }

    private function datos(string $accion = 'validar'): array
    {
        $datos = ['version' => $this->pedido->fresh()->version, 'confirmacion' => true];
        if ($accion !== 'validar') {
            $datos['clave_operacion'] = (string) Str::uuid();
        }
        if ($accion === 'terminar') {
            $datos += ['confirmar_precio' => true, 'validar_materiales' => true];
        }

        return $datos;
    }

    private function cerrar(): void
    {
        $this->usoCompleto();
        $this->actingAs($this->propietario)->postJson(route('admin.fabricacion.terminar', $this->pedido), $this->datos('terminar'))->assertRedirect();
    }

    private function huellas(): array
    {
        $resultado = [];
        foreach (['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'existencias_materiales', 'movimientos_materiales', 'cotizaciones', 'detalles_cotizacion', 'materiales_cotizacion', 'detalles_pedido'] as $tabla) {
            $resultado[$tabla] = DB::table($tabla)->orderByRaw('1')->get()->toJson();
        }

        return $resultado;
    }

    public function test_maestro_valida_cero_expreso_sin_cerrar_ni_alterar_precio_o_saldos(): void
    {
        $this->usoCompleto();
        $this->get(route('admin.pedidos.show', $this->pedido))->assertOk()->assertSee('Validar materiales')->assertDontSee('Terminar fabricación');
        $fila = MaterialPedido::first();
        $fila->cantidad_utilizada = '0';
        $fila->save();
        $antes = $this->huellas();
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertRedirect();
        $pedido = $this->pedido->fresh();
        $this->assertSame('en_fabricacion', $pedido->estado);
        $this->assertSame($this->maestro->id, $pedido->materiales_validados_por_id);
        $this->assertNotNull($pedido->materiales_validados_at);
        $this->assertNull($pedido->precio_confirmado_at);
        $this->assertSame($antes, $this->huellas());
        $this->get(route('admin.pedidos.show', $pedido))->assertOk()->assertSee('Materiales validados')->assertDontSee('Terminar fabricación');
    }

    public function test_pendientes_y_mueble_sin_materiales_impiden_validacion_y_cierre(): void
    {
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertUnprocessable()->assertJsonValidationErrors('materiales');
        $this->actingAs($this->propietario)->postJson(route('admin.fabricacion.terminar', $this->pedido), $this->datos('terminar'))->assertUnprocessable()->assertJsonValidationErrors('materiales');
        $this->usoCompleto();
        MaterialPedido::where('detalle_pedido_id', $this->pedido->detalles()->first()->id)->delete();
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertUnprocessable()->assertJsonValidationErrors('materiales');
        $this->assertNull($this->pedido->fresh()->materiales_validados_at);
    }

    public function test_propietario_valida_y_termina_en_una_accion_preservando_el_acuerdo(): void
    {
        $this->actingAs($this->propietario)->get(route('admin.pedidos.show', $this->pedido))->assertOk()
            ->assertSee('Revisé los materiales y confirmo el precio acordado:')
            ->assertSee('name="validar_materiales" value="1"', false)
            ->assertDontSee('id="validar-al-terminar"', false)
            ->assertDontSee('>Validar materiales</button>', false);
        $antes = $this->huellas();
        $this->cerrar();
        $pedido = $this->pedido->fresh();
        $this->assertSame('terminado', $pedido->estado);
        $this->assertSame($this->propietario->id, $pedido->materiales_validados_por_id);
        $this->assertSame($this->propietario->id, $pedido->precio_confirmado_por_id);
        $this->assertNotNull($pedido->precio_confirmado_at);
        $this->assertSame($antes, $this->huellas());
        $this->assertSame(1, $pedido->estados()->where('estado_nuevo', 'terminado')->count());
        $this->assertDatabaseHas('auditorias', ['accion' => 'fabricacion_terminada']);
        $this->get(route('admin.pedidos.show', $pedido))->assertOk()->assertSee('Registrar entrega')->assertDontSee('Registrar materiales utilizados');
    }

    public function test_validacion_del_maestro_se_conserva_al_cerrar_propietario(): void
    {
        $this->usoCompleto();
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertRedirect();
        $fecha = $this->pedido->fresh()->materiales_validados_at;
        $datos = $this->datos('terminar');
        unset($datos['validar_materiales']);
        $this->actingAs($this->propietario)->postJson(route('admin.fabricacion.terminar', $this->pedido), $datos)->assertRedirect();
        $this->assertSame($this->maestro->id, $this->pedido->fresh()->materiales_validados_por_id);
        $this->assertTrue($fecha->equalTo($this->pedido->fresh()->materiales_validados_at));
    }

    public function test_precio_exige_confirmacion_y_correspondencia_sin_actualizarlo(): void
    {
        $this->usoCompleto();
        $this->actingAs($this->propietario);
        $datos = $this->datos('terminar');
        unset($datos['confirmar_precio']);
        $this->postJson(route('admin.fabricacion.terminar', $this->pedido), $datos)->assertUnprocessable()->assertJsonValidationErrors('confirmar_precio');
        $this->pedido->precio_total_acordado = '999';
        $this->pedido->save();
        $this->postJson(route('admin.fabricacion.terminar', $this->pedido), $this->datos('terminar'))->assertUnprocessable()->assertJsonValidationErrors('precio');
        $this->assertSame('999.00', $this->pedido->fresh()->precio_total_acordado);
        $this->assertSame('en_fabricacion', $this->pedido->fresh()->estado);
        $this->assertNull($this->pedido->fresh()->materiales_validados_at);
    }

    public function test_maestro_no_termina_ni_entrega_incluso_con_permiso_comercial_incompatible(): void
    {
        $this->usoCompleto();
        $this->maestro->givePermissionTo('cotizaciones.validar');
        $this->postJson(route('admin.fabricacion.terminar', $this->pedido), $this->datos('terminar'))->assertForbidden();
        $this->postJson(route('admin.pedidos.entregar', $this->pedido), $this->datos('entregar'))->assertForbidden();
        $this->assertSame('en_fabricacion', $this->pedido->fresh()->estado);
    }

    public function test_permisos_de_validacion_y_cierre_se_exigen_individualmente(): void
    {
        $soloCierre = User::factory()->create();
        $soloCierre->givePermissionTo(['panel.consultar', 'pedidos.consultar', 'pedidos.modificar', 'planificacion.consultar', 'cotizaciones.validar', 'pedidos.terminar']);
        $this->actingAs($soloCierre)->get(route('admin.pedidos.show', $this->pedido))->assertOk()
            ->assertSee('Una persona con permiso para validar materiales debe revisarlos antes del cierre.')
            ->assertDontSee('name="validar_materiales"', false)
            ->assertDontSee('data-pago-saldo', false)
            ->assertDontSee('data-pago-pagado', false);
        $this->actingAs($this->maestro);
        $this->usoCompleto();
        foreach (['pedidos.consultar', 'planificacion.consultar', 'pedidos.modificar', 'planificacion.modificar', 'materiales.validar'] as $permiso) {
            $rol = $this->maestro->roles->first();
            $rol->revokePermissionTo($permiso);
            $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertForbidden();
            $rol->givePermissionTo($permiso);
        }
        $this->propietario->roles->first()->revokePermissionTo('cotizaciones.validar');
        $this->actingAs($this->propietario)->postJson(route('admin.fabricacion.terminar', $this->pedido), $this->datos('terminar'))->assertForbidden();
        $this->assertNull($this->pedido->fresh()->materiales_validados_at);
    }

    public function test_cambios_de_uso_invalidan_revision_y_guardado_identico_la_conserva(): void
    {
        $this->usoCompleto();
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertRedirect();
        $filas = MaterialPedido::orderBy('id')->get()->map(fn ($fila) => $fila->only(['id', 'detalle_pedido_id', 'material_id', 'cantidad_utilizada', 'observaciones_uso']))->all();
        $version = $this->pedido->fresh()->version;
        app(FabricacionService::class)->guardar($this->maestro, $this->pedido, ['version' => $version, 'materiales' => $filas]);
        $this->assertNotNull($this->pedido->fresh()->materiales_validados_at);
        $this->assertSame($version, $this->pedido->fresh()->version);
        $filas[0]['observaciones_uso'] = 'Observación operativa cambiada';
        app(FabricacionService::class)->guardar($this->maestro, $this->pedido, ['version' => $version, 'materiales' => $filas]);
        $this->assertNull($this->pedido->fresh()->materiales_validados_at);
        $this->assertDatabaseHas('auditorias', ['accion' => 'validacion_materiales_invalidada']);
        $datos = $this->datos('terminar');
        $datos['validar_materiales'] = false;
        $this->actingAs($this->propietario)->postJson(route('admin.fabricacion.terminar', $this->pedido), $datos)->assertUnprocessable()->assertJsonValidationErrors('materiales');
    }

    public function test_planificacion_identica_y_notas_generales_conservan_revision_pero_cambio_real_la_invalida(): void
    {
        $this->usoCompleto();
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertRedirect();
        $plan = ['version' => $this->pedido->fresh()->version, 'motivo' => 'Revisión de planificación', 'materiales' => MaterialPedido::orderBy('id')->get()->map(fn ($fila) => $fila->only(['id', 'detalle_pedido_id', 'material_id', 'cantidad_planificada', 'observaciones']))->all()];
        app(PlanificacionMaterialService::class)->guardar($this->maestro, $this->pedido, $plan);
        $this->assertNotNull($this->pedido->fresh()->materiales_validados_at);
        app(PedidoService::class)->actualizar($this->maestro, $this->pedido, ['version' => $this->pedido->fresh()->version, 'observaciones' => 'Nota general sin modificar materiales']);
        $this->assertNotNull($this->pedido->fresh()->materiales_validados_at);
        $plan['version'] = $this->pedido->fresh()->version;
        $plan['materiales'][0]['cantidad_planificada'] = '7';
        app(PlanificacionMaterialService::class)->guardar($this->maestro, $this->pedido, $plan);
        $this->assertNull($this->pedido->fresh()->materiales_validados_por_id);
    }

    public function test_cierre_y_entrega_idempotentes_y_clave_reutilizada_con_otros_datos_rechazada(): void
    {
        $this->usoCompleto();
        $this->actingAs($this->propietario);
        $datos = $this->datos('terminar');
        $ruta = route('admin.fabricacion.terminar', $this->pedido);
        $this->postJson($ruta, $datos)->assertRedirect();
        $version = $this->pedido->fresh()->version;
        $this->postJson($ruta, $datos)->assertRedirect();
        $this->assertSame($version, $this->pedido->fresh()->version);
        $datos['version']++;
        $this->postJson($ruta, $datos)->assertUnprocessable()->assertJsonValidationErrors('clave_operacion');
        $this->postJson($ruta, $this->datos('terminar'))->assertUnprocessable()->assertJsonValidationErrors('estado');
        $entrega = $this->datos('entregar');
        $entrega['observaciones'] = 'Entregado en taller';
        $this->postJson(route('admin.pedidos.entregar', $this->pedido), $entrega)->assertRedirect();
        $this->postJson(route('admin.pedidos.entregar', $this->pedido), $entrega)->assertRedirect();
        $this->assertSame('entregado', $this->pedido->fresh()->estado);
        $this->assertSame(1, $this->pedido->estados()->where('estado_nuevo', 'entregado')->count());
        $this->assertDatabaseHas('estados_pedido', ['estado_nuevo' => 'entregado', 'motivo' => 'Entregado en taller', 'usuario_id' => $this->propietario->id]);
    }

    public function test_no_hay_saltos_entrega_prematura_ni_escrituras_despues_de_terminado(): void
    {
        $this->actingAs($this->propietario)->postJson(route('admin.pedidos.entregar', $this->pedido), $this->datos('entregar'))->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->cerrar();
        $this->get(route('admin.fabricacion.edit', $this->pedido))->assertForbidden();
        $this->get(route('admin.planificacion.edit', $this->pedido))->assertForbidden();
        $this->putJson(route('admin.pedidos.update', $this->pedido), ['version' => $this->pedido->fresh()->version, 'observaciones' => 'Cambio prohibido'])->assertForbidden();
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $this->datos())->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->postJson(route('admin.pedidos.estado', $this->pedido), ['version' => $this->pedido->fresh()->version, 'estado' => 'entregado', 'clave_operacion' => (string) Str::uuid()])->assertUnprocessable();
    }

    public function test_campos_privilegiados_confirmacion_version_y_estado_se_validan_en_servidor(): void
    {
        $this->usoCompleto();
        $this->actingAs($this->propietario);
        foreach (['precio_total_acordado' => 1, 'estado' => 'terminado', 'materiales_validados_por_id' => $this->propietario->id, 'precio_confirmado_at' => '2020-01-01'] as $campo => $valor) {
            $this->postJson(route('admin.fabricacion.terminar', $this->pedido), [...$this->datos('terminar'), $campo => $valor])->assertUnprocessable()->assertJsonValidationErrors('solicitud');
        }
        $datos = $this->datos();
        $datos['confirmacion'] = false;
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $datos)->assertUnprocessable()->assertJsonValidationErrors('confirmacion');
        $datos = $this->datos();
        $datos['version'] = 1;
        $this->postJson(route('admin.fabricacion.validar', $this->pedido), $datos)->assertUnprocessable()->assertJsonValidationErrors('version');
    }

    public function test_fallo_de_auditoria_revierte_cierre_validaciones_y_evento(): void
    {
        $this->usoCompleto();
        $version = $this->pedido->fresh()->version;
        $auditoria = Mockery::mock(AuditoriaService::class)->makePartial();
        $auditoria->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Rollback de prueba 4D'));
        $this->app->instance(AuditoriaService::class, $auditoria);
        try {
            app(CierrePedidoService::class)->terminar($this->propietario, $this->pedido, $this->datos('terminar'));
            $this->fail('Se esperaba rollback.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Rollback de prueba 4D', $error->getMessage());
        }
        $this->assertSame('en_fabricacion', $this->pedido->fresh()->estado);
        $this->assertSame($version, $this->pedido->fresh()->version);
        $this->assertNull($this->pedido->fresh()->precio_confirmado_at);
        $this->assertNull($this->pedido->fresh()->materiales_validados_at);
        $this->assertSame(0, $this->pedido->estados()->where('estado_nuevo', 'terminado')->count());
    }

    public function test_servicio_rechaza_actor_inactivo_sin_confiar_en_sesion_previa(): void
    {
        $this->usoCompleto();
        $this->propietario->activo = false;
        $this->propietario->save();
        $this->expectException(AuthorizationException::class);
        app(CierrePedidoService::class)->terminar($this->propietario,$this->pedido,$this->datos('terminar'));
    }
}
