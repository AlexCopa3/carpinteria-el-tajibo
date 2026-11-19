<?php

namespace Tests\Feature;

use App\Models\Auditoria;
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

class FabricacionTest extends TestCase
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

    private function datos(): array
    {
        return ['version' => $this->pedido->fresh()->version, 'materiales' => MaterialPedido::orderBy('id')->get()->map(fn ($fila) => $fila->only(['id', 'detalle_pedido_id', 'material_id', 'cantidad_utilizada', 'observaciones_uso']))->all()];
    }

    private function guardar(array $datos)
    {
        return $this->putJson(route('admin.fabricacion.update', $this->pedido), $datos);
    }

    private function huellas(): array
    {
        $resultado = [];
        foreach (['materiales', 'existencias_materiales', 'movimientos_materiales', 'detalles_pedido', 'estados_pedido'] as $tabla) {
            $resultado[$tabla] = DB::table($tabla)->orderBy('id')->get()->toJson();
        }
        $resultado['cotizacion_id'] = $this->pedido->fresh()->cotizacion_id;
        $resultado['precio_aceptado'] = $this->pedido->fresh()->precio_total_acordado;
        return $resultado;
    }

    public function test_pantallas_y_campos_iniciales_pendientes_no_simulan_consumos(): void
    {
        $antes = $this->huellas();
        foreach (MaterialPedido::all() as $fila) {
            $this->assertNull($fila->cantidad_utilizada);
            $this->assertNull($fila->uso_registrado_por_id);
            $this->assertNull($fila->uso_registrado_at);
        }
        $this->get(route('admin.fabricacion.edit', $this->pedido))->assertOk()->assertDontSee('Copiar cantidades planificadas')->assertSee('Cancelar');
        $this->get(route('admin.pedidos.show', $this->pedido))->assertOk()->assertSee('Sin registrar');
        $this->get(route('admin.planificacion.show', $this->pedido))->assertOk()->assertSee('Sin registrar');
        $this->assertSame($antes, $this->huellas());
    }

    public function test_maestro_guarda_totales_sin_repetir_multiplicacion_ni_cambiar_saldos_o_precios(): void
    {
        $antes = $this->huellas();
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '6.5000';
        $datos['materiales'][0]['observaciones_uso'] = 'Cantidad observada en prueba';
        $this->guardar($datos)->assertRedirect(route('admin.pedidos.show', $this->pedido));
        $fila = MaterialPedido::firstOrFail();
        $this->assertSame('6.0000', $fila->cantidad_inicial);
        $this->assertSame('6.0000', $fila->cantidad_planificada);
        $this->assertSame('6.5000', $fila->cantidad_utilizada);
        $this->assertSame('0.5000', (string) $fila->diferencia_utilizada);
        $this->assertSame($this->maestro->id, $fila->uso_registrado_por_id);
        $this->assertNotNull($fila->uso_registrado_at);
        $this->assertNull(MaterialPedido::orderBy('id')->skip(1)->first()->cantidad_utilizada);
        $this->assertSame('en_fabricacion', $this->pedido->fresh()->estado);
        $this->assertSame($antes, $this->huellas());
        $this->assertSame($this->maestro->id, Auditoria::where('accion', 'uso_materiales_registrado')->sole()->usuario_id);
    }

    public function test_cero_y_pendiente_son_diferentes_y_vaciar_exige_motivo(): void
    {
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '0';
        $this->guardar($datos)->assertRedirect();
        $this->assertSame('0.0000', MaterialPedido::first()->cantidad_utilizada);
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = null;
        $this->guardar($datos)->assertUnprocessable()->assertJsonValidationErrors('motivo_correccion');
        $datos['motivo_correccion'] = 'Se vuelve a revisar la cantidad';
        $this->guardar($datos)->assertRedirect();
        $fila = MaterialPedido::first();
        $this->assertNull($fila->cantidad_utilizada);
        $this->assertNull($fila->uso_registrado_por_id);
        $this->assertNull($fila->uso_registrado_at);
        $this->assertSame(2, Auditoria::where('accion', 'uso_materiales_registrado')->count());
    }

    public function test_correccion_requiere_motivo_y_conserva_valores_anteriores(): void
    {
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '6';
        $this->guardar($datos)->assertRedirect();
        $version = $this->pedido->fresh()->version;
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '4';
        $datos['motivo_correccion'] = '     ';
        $this->guardar($datos)->assertUnprocessable();
        $this->assertSame($version, $this->pedido->fresh()->version);
        $this->assertSame('6.0000', MaterialPedido::first()->cantidad_utilizada);
        $datos['motivo_correccion'] = 'Corrección después de medir';
        $this->actingAs($this->propietario);
        $this->guardar($datos)->assertRedirect();
        $this->assertSame('4.0000', MaterialPedido::first()->cantidad_utilizada);
        $this->assertSame($this->propietario->id, MaterialPedido::first()->uso_registrado_por_id);
        $auditoria = Auditoria::where('accion', 'uso_materiales_registrado')->latest('id')->first();
        $this->assertSame('6.0000', $auditoria->datos_anteriores['materiales'][0]['cantidad_utilizada']);
        $this->assertSame('4.0000', $auditoria->datos_nuevos['materiales'][0]['cantidad_utilizada']);
    }

    public function test_sin_cambios_y_reenvio_no_generan_filas_o_auditorias_duplicadas(): void
    {
        $cantidadAuditorias = Auditoria::count();
        $version = $this->pedido->fresh()->version;
        $this->guardar($this->datos())->assertRedirect()->assertSessionHas('mensaje', 'Sin cambios. Las cantidades vacías continúan pendientes.');
        $this->assertSame($cantidadAuditorias, Auditoria::count());
        $this->assertSame($version, $this->pedido->fresh()->version);
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '6';
        $this->guardar($datos)->assertRedirect();
        $this->guardar($datos)->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->guardar($this->datos())->assertRedirect();
        $this->assertSame($cantidadAuditorias + 1, Auditoria::count());
        $this->assertDatabaseCount('materiales_pedido', 2);
    }

    public function test_material_adicional_tiene_plan_cero_y_actualiza_la_cotizacion(): void
    {
        $antes = $this->huellas();
        $datos = $this->datos();
        $datos['materiales'][] = ['detalle_pedido_id' => $datos['materiales'][0]['detalle_pedido_id'], 'material_id' => $this->adicional->id, 'cantidad_utilizada' => '4'];
        $this->guardar($datos)->assertRedirect();
        $fila = MaterialPedido::where('material_id', $this->adicional->id)->sole();
        $this->assertNull($fila->cantidad_inicial);
        $this->assertNotNull($fila->material_cotizacion_id);
        $this->assertSame('0.0000', $fila->cantidad_planificada);
        $this->assertSame('4.0000', $fila->cantidad_utilizada);
        $this->assertSame($antes, $this->huellas());
    }

    public function test_no_inserta_filas_nuevas_sin_cantidad_ni_materiales_duplicados(): void
    {
        $datos = $this->datos();
        $extra = ['detalle_pedido_id' => $datos['materiales'][0]['detalle_pedido_id'], 'material_id' => $this->adicional->id, 'cantidad_utilizada' => null];
        $datos['materiales'][] = $extra;
        $this->guardar($datos)->assertRedirect();
        $this->assertDatabaseCount('materiales_pedido', 2);
        $extra['cantidad_utilizada'] = '2';
        $datos = $this->datos();
        $datos['materiales'][] = $extra;
        $datos['materiales'][] = $extra;
        $this->guardar($datos)->assertUnprocessable();
        $this->assertDatabaseCount('materiales_pedido', 2);
        $datos = $this->datos();
        $extra['material_id'] = $this->material->id;
        $datos['materiales'][] = $extra;
        $this->guardar($datos)->assertUnprocessable();
    }

    public function test_rechaza_negativos_exponentes_precision_excesiva_y_fracciones_en_piezas(): void
    {
        foreach (['-1', '1e3', '2.12345', '100000000000000', '1,5', true, [], 2.5] as $cantidad) {
            $datos = $this->datos();
            $datos['materiales'][0]['cantidad_utilizada'] = $cantidad;
            $this->guardar($datos)->assertUnprocessable();
        }
        $datos = $this->datos();
        $datos['materiales'][] = ['detalle_pedido_id' => $datos['materiales'][0]['detalle_pedido_id'], 'material_id' => $this->adicional->id, 'cantidad_utilizada' => '0.5'];
        $this->guardar($datos)->assertUnprocessable();
        $this->assertNull(MaterialPedido::first()->cantidad_utilizada);
    }

    public function test_rechaza_campos_administrativos_y_actor_unidad_o_fechas_del_cliente(): void
    {
        foreach (['estado', 'precio_total_acordado', 'uso_registrado_por_id'] as $campo) {
            $datos = $this->datos();
            $datos[$campo] = '1';
            $this->guardar($datos)->assertUnprocessable();
        }
        foreach (['unidad_id', 'cantidad_planificada', 'uso_registrado_at', 'uso_registrado_por_id'] as $campo) {
            $datos = $this->datos();
            $datos['materiales'][0][$campo] = '1';
            $this->guardar($datos)->assertUnprocessable();
        }
        $datos = $this->datos();
        unset($datos['materiales'][0]['cantidad_utilizada']);
        $this->guardar($datos)->assertUnprocessable();
    }

    public function test_no_omite_filas_existentes_ni_acepta_ids_duplicados_o_ajenos(): void
    {
        $datos = $this->datos();
        array_pop($datos['materiales']);
        $datos['materiales'][0]['cantidad_utilizada'] = '1';
        $this->guardar($datos)->assertUnprocessable();
        $datos = $this->datos();
        $datos['materiales'][] = $datos['materiales'][0];
        $this->guardar($datos)->assertUnprocessable();
        $datos = $this->datos();
        $datos['materiales'][0]['detalle_pedido_id'] = $datos['materiales'][1]['detalle_pedido_id'];
        $this->guardar($datos)->assertUnprocessable();
        $datos = $this->datos();
        $datos['materiales'][0]['id'] = 999999;
        $this->guardar($datos)->assertUnprocessable();
        $datos = $this->datos();
        $datos['materiales'][0]['detalle_pedido_id'] = 999999;
        $this->guardar($datos)->assertUnprocessable();
        $this->assertNull(MaterialPedido::first()->cantidad_utilizada);
    }

    public function test_inactivo_historico_se_conserva_y_un_adicional_inactivo_se_rechaza(): void
    {
        $this->material->activo = false;
        $this->material->save();
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '1';
        $this->guardar($datos)->assertRedirect();
        $revision = $this->pedido->cotizacion()->firstOrFail();
        $this->assertSame('1.0000', $revision->detalles->first()->materiales->first()->cantidad_total_aplicada);
        $this->assertSame('85.00', $revision->detalles->first()->costo_total);
        $this->adicional->activo = false;
        $this->adicional->save();
        $datos = $this->datos();
        $datos['materiales'][] = ['detalle_pedido_id' => $datos['materiales'][0]['detalle_pedido_id'], 'material_id' => $this->adicional->id, 'cantidad_utilizada' => '1'];
        $this->guardar($datos)->assertUnprocessable();
        $this->assertDatabaseCount('materiales_pedido', 2);
    }

    public function test_unidad_inconsistente_se_rechaza_y_diferencia_usa_decimal_exacto(): void
    {
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '6.0001';
        $this->guardar($datos)->assertRedirect();
        $this->assertSame('0.0001', (string) MaterialPedido::first()->diferencia_utilizada);
        DB::table('materiales')->where('id', $this->material->id)->update(['unidad_id' => $this->adicional->unidad_id]);
        $this->guardar($this->datos())->assertUnprocessable();
    }

    public function test_solo_en_fabricacion_y_con_los_cuatro_permisos_del_diseno(): void
    {
        foreach (['aceptado', 'terminado', 'entregado'] as $estado) {
            DB::table('pedidos')->where('id', $this->pedido->id)->update(['estado' => $estado]);
            $this->get(route('admin.fabricacion.edit', $this->pedido))->assertForbidden();
            $this->guardar($this->datos())->assertForbidden();
        }
        DB::table('pedidos')->where('id', $this->pedido->id)->update(['estado' => 'en_fabricacion']);
        foreach (['pedidos.consultar', 'pedidos.modificar', 'planificacion.consultar', 'planificacion.modificar'] as $permiso) {
            $usuario = User::factory()->create();
            $usuario->givePermissionTo(array_diff(['panel.consultar', 'pedidos.consultar', 'pedidos.modificar', 'planificacion.consultar', 'planificacion.modificar'], [$permiso]));
            $this->actingAs($usuario);
            $this->guardar($this->datos())->assertForbidden();
        }
    }

    public function test_servicio_no_permite_usuario_inactivo_ni_campos_extra(): void
    {
        $this->maestro->activo = false;
        $this->maestro->save();
        $this->expectException(AuthorizationException::class);
        app(FabricacionService::class)->guardar($this->maestro, $this->pedido, $this->datos());
    }

    public function test_fallo_de_auditoria_revierte_todo_incluida_fila_adicional(): void
    {
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '6';
        $datos['materiales'][] = ['detalle_pedido_id' => $datos['materiales'][0]['detalle_pedido_id'], 'material_id' => $this->adicional->id, 'cantidad_utilizada' => '2'];
        $version = $this->pedido->version;
        $auditoria = Mockery::mock(AuditoriaService::class);
        $auditoria->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Fallo simulado aislado'));
        $this->app->instance(AuditoriaService::class, $auditoria);
        try {
            app(FabricacionService::class)->guardar($this->maestro, $this->pedido, $datos);
            $this->fail('Se esperaba rollback.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo simulado aislado', $error->getMessage());
        }
        $this->assertSame($version, $this->pedido->fresh()->version);
        $this->assertNull(MaterialPedido::first()->cantidad_utilizada);
        $this->assertDatabaseCount('materiales_pedido', 2);
        $this->assertSame(0, Auditoria::where('accion', 'uso_materiales_registrado')->count());
    }

    public function test_planificacion_posterior_conserva_el_uso_y_las_notas_omitidas_no_se_borran(): void
    {
        $datos = $this->datos();
        $datos['materiales'][0]['cantidad_utilizada'] = '6';
        $datos['materiales'][0]['observaciones_uso'] = 'Nota de uso que se conserva';
        $this->guardar($datos)->assertRedirect();
        $actor = MaterialPedido::first()->uso_registrado_por_id;
        $plan = ['version' => $this->pedido->fresh()->version, 'motivo' => 'Cambio de planificación aislado', 'materiales' => MaterialPedido::orderBy('id')->get()->map(fn ($fila) => $fila->only(['id', 'detalle_pedido_id', 'material_id', 'cantidad_planificada', 'observaciones']))->all()];
        $plan['materiales'][0]['cantidad_planificada'] = '8';
        app(PlanificacionMaterialService::class)->guardar($this->propietario, $this->pedido, $plan);
        $fila = MaterialPedido::first();
        $this->assertSame('6.0000', $fila->cantidad_utilizada);
        $this->assertSame($actor, $fila->uso_registrado_por_id);
        $datos = $this->datos();
        unset($datos['materiales'][0]['observaciones_uso']);
        $this->guardar($datos)->assertRedirect();
        $this->assertSame('Nota de uso que se conserva', MaterialPedido::first()->observaciones_uso);
    }
}
