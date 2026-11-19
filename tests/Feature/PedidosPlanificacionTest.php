<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EstadoPedido;
use App\Models\ExistenciaMaterial;
use App\Models\Material;
use App\Models\MaterialPedido;
use App\Models\Pedido;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CatalogoService;
use App\Services\CotizacionService;
use App\Services\PedidoService;
use App\Services\PlanificacionMaterialService;
use Database\Seeders\FaseCuatroBPermisosSeeder;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PedidosPlanificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $maestro;

    private Cotizacion $cotizacion;

    private Material $material;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed([PermisosSeeder::class, UnidadesSeeder::class, TiposMuebleSeeder::class]);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $this->cliente = Cliente::create(['nombre' => 'Cliente de prueba 4B', 'tipo' => 'persona', 'telefono' => '70000000']);
        $this->material = app(CatalogoService::class)->guardar($this->propietario, ['nombre' => 'Material aislado', 'categoria_id' => Categoria::create(['nombre' => 'Material principal'])->id, 'unidad_id' => UnidadMaterial::where('codigo', 'metro')->value('id'), 'precio_referencia' => '40', 'activo' => true]);
        $detalle = ['tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'), 'material_principal_id' => $this->material->id, 'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural', 'cantidad_muebles' => 1, 'mano_obra_unitaria' => '20', 'otros_costos_total' => '5', 'materiales' => [['material_id' => $this->material->id, 'componente' => 'General', 'cantidad_unitaria' => '3', 'costo_unitario_estimado' => '40', 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo']]];
        $otro = $detalle;
        $otro['materiales'][0]['cantidad_unitaria'] = '5';
        $service = app(CotizacionService::class);
        $this->cotizacion = $service->guardar($this->propietario, ['clave_registro' => Str::uuid()->toString(), 'fecha' => today()->format('Y-m-d'), 'detalles' => [$detalle, $otro]]);
        $this->cotizacion = $service->aprobar($this->propietario, $this->cotizacion, ['version' => $this->cotizacion->version, 'precios' => $this->cotizacion->detalles->mapWithKeys(fn ($detalle) => [$detalle->id => '300'])->all()]);
        $this->actingAs($this->propietario);
    }

    private function datos(): array
    {
        return ['cotizacion_id' => $this->cotizacion->id, 'clave_registro' => Str::uuid()->toString(), 'aceptacion' => true, 'cliente_id' => $this->cliente->id];
    }

    private function crear(): Pedido
    {
        return app(PedidoService::class)->crear($this->propietario, $this->datos());
    }

    private function plan(Pedido $pedido): array
    {
        return ['version' => $pedido->version, 'motivo' => 'Revisión de cantidades en el taller', 'materiales' => $pedido->detalles->flatMap->materiales->map(fn ($fila) => $fila->only(['id', 'detalle_pedido_id', 'material_id', 'cantidad_planificada', 'observaciones']))->values()->all()];
    }

    public function test_fecha_pasada_informa_en_espanol_y_no_crea_pedido(): void
    {
        $datos = $this->datos();
        $datos['fecha_prevista_entrega'] = today()->subDay()->format('Y-m-d');
        $this->postJson('/admin/pedidos/create', $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha_prevista_entrega')
            ->assertJsonPath('errors.fecha_prevista_entrega.0', 'La entrega prevista debe ser hoy o una fecha posterior.');
        $this->assertDatabaseCount('pedidos', 0);

        $datos['fecha_prevista_entrega'] = today()->format('Y-m-d');
        $this->postJson('/admin/pedidos/create', $datos)->assertRedirect();
        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_aceptacion_atomica_sin_duplicados_conserva_precio_y_cotizacion(): void
    {
        $antes = $this->cotizacion->getAttributes();
        $datos = $this->datos();
        $this->postJson('/admin/pedidos/create', $datos)->assertRedirect();
        $pedido = Pedido::sole();
        $this->postJson('/admin/pedidos/create', $datos)->assertRedirect();
        $datos['clave_registro'] = Str::uuid()->toString();
        $this->postJson('/admin/pedidos/create', $datos)->assertRedirect();
        $this->assertDatabaseCount('pedidos', 1);
        $this->assertDatabaseCount('detalles_pedido', 2);
        $this->assertDatabaseCount('materiales_pedido', 2);
        $this->assertDatabaseCount('estados_pedido', 1);
        $this->assertSame('600.00', $pedido->precio_total_acordado);
        $this->assertSame($antes, $this->cotizacion->fresh()->getAttributes());
        $this->get('/admin/cotizaciones/'.$this->cotizacion->id)->assertOk()->assertSee('Ver pedido');
        $datos['observaciones'] = 'Otro intento';
        $this->postJson('/admin/pedidos/create', $datos)->assertUnprocessable();
        $this->cliente->update(['nombre' => 'Nuevo contacto']);
        $this->assertSame('Cliente de prueba 4B', $pedido->fresh()->datos_cliente_acordados['nombre']);
    }

    public function test_copia_cantidades_aplicadas_sin_segunda_multiplicacion(): void
    {
        // Simula un antecedente aprobado con lote y ahorro ya registrado, solo en SQLite.
        $detalle = $this->cotizacion->detalles->first();
        DB::table('detalles_cotizacion')->where('id', $detalle->id)->update(['cantidad_muebles' => 3]);
        DB::table('materiales_cotizacion')->where('detalle_cotizacion_id', $detalle->id)->update(['cantidad_total_nominal' => '9', 'cantidad_total_aplicada' => '8.5', 'motivo_ajuste_cantidad' => 'Aprovechamiento conjunto']);
        $pedido = $this->crear();
        $this->assertSame('8.5000', $pedido->detalles->first()->materiales->sole()->cantidad_inicial);
        $this->assertSame('300.00', $pedido->detalles->first()->precio_acordado);
        $this->assertSame('600.00', $pedido->precio_total_acordado);
    }

    public function test_nuevo_cliente_y_error_de_auditoria_revierten_toda_la_aceptacion(): void
    {
        $datos = $this->datos();
        unset($datos['cliente_id']);
        $datos['cliente'] = ['nombre' => 'Alta aislada', 'tipo' => 'persona', 'telefono' => '71000000'];
        $this->instance(AuditoriaService::class, \Mockery::mock(AuditoriaService::class, function ($mock) {
            $mock->shouldReceive('datos')->andReturn([]);
            $mock->shouldReceive('registrar')->andThrow(new \RuntimeException('Fallo de auditoría'));
        }));
        try {
            app(PedidoService::class)->crear($this->propietario, $datos);
            $this->fail('Debe revertir');
        } catch (\RuntimeException $error) {
            $this->assertSame('Fallo de auditoría', $error->getMessage());
        }
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('materiales_pedido', 0);
    }

    public function test_cliente_nuevo_minimo_y_datos_invalidos(): void
    {
        $datos = $this->datos();
        unset($datos['cliente_id']);
        $this->postJson('/admin/pedidos/create', $datos)->assertUnprocessable();
        $datos['cliente'] = ['nombre' => 'Alta aislada', 'tipo' => 'persona', 'telefono' => '71000000'];
        $this->postJson('/admin/pedidos/create', $datos)->assertRedirect();
        $this->assertDatabaseCount('clientes', 2);
        $this->assertSame('Alta aislada', Pedido::sole()->datos_cliente_acordados['nombre']);
    }

    public function test_no_acepta_borrador_cliente_inactivo_o_campos_inyectados(): void
    {
        foreach (['precio_total_acordado', 'registrado_por_id', 'estado'] as $campo) {
            $this->postJson('/admin/pedidos/create', $this->datos() + [$campo => '1'])->assertUnprocessable();
        }
        $this->cliente->update(['activo' => false]);
        $this->postJson('/admin/pedidos/create', $this->datos())->assertUnprocessable();
        $this->cliente->update(['activo' => true]);
        DB::table('cotizaciones')->where('id', $this->cotizacion->id)->update(['aprobado_at' => null]);
        $this->postJson('/admin/pedidos/create', $this->datos())->assertUnprocessable();
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_maestro_no_acepta_ni_modifica_campos_administrativos_aun_con_permiso_erroneo(): void
    {
        $pedido = $this->crear();
        $this->maestro->givePermissionTo('pedidos.crear');
        \App\Models\PermisoUsuario::create(['user_id' => $this->maestro->id, 'permission_id' => \Spatie\Permission\Models\Permission::findByName('pedidos.crear')->id, 'permitido' => false]);
        $this->maestro->unsetRelation('permisosIndividuales');
        $this->actingAs($this->maestro)->postJson('/admin/pedidos/create', $this->datos())->assertForbidden();
        $this->putJson('/admin/pedidos/'.$pedido->id, ['version' => 1, 'responsable_id' => $this->maestro->id])->assertForbidden();
        $this->putJson('/admin/pedidos/'.$pedido->id, ['version' => 1, 'cliente_id' => $this->cliente->id])->assertUnprocessable();
        $this->get('/admin/pedidos')->assertOk();
        $this->get('/admin/pedidos/'.$pedido->id)->assertOk()->assertDontSee('Ganancia sobre el costo');
        $this->get('/admin/planificacion/'.$pedido->id)->assertOk();
        $this->putJson('/admin/pedidos/'.$pedido->id, ['version' => 1, 'observaciones' => 'Revisión operativa'])->assertRedirect();
        $this->assertSame('Revisión operativa', $pedido->fresh()->observaciones);
    }

    public function test_transicion_idempotente_y_cierre_protegido_sin_movimientos(): void
    {
        $pedido = $this->crear();
        $datos = ['version' => 1, 'estado' => 'en_fabricacion', 'clave_operacion' => Str::uuid()->toString()];
        $this->actingAs($this->maestro)->postJson('/admin/pedidos/'.$pedido->id.'/estado', $datos)->assertRedirect();
        $this->postJson('/admin/pedidos/'.$pedido->id.'/estado', $datos)->assertRedirect();
        $this->assertDatabaseCount('estados_pedido', 2);
        $this->assertSame('en_fabricacion', $pedido->fresh()->estado);
        $this->postJson('/admin/pedidos/'.$pedido->id.'/estado', array_replace($datos, ['clave_operacion' => Str::uuid()->toString()]))->assertUnprocessable();
        foreach (['terminado', 'entregado', 'aceptado'] as $estado) {
            $this->postJson('/admin/pedidos/'.$pedido->id.'/estado', array_replace($datos, ['version' => 2, 'estado' => $estado]))->assertUnprocessable();
        }
        $this->assertDatabaseCount('movimientos_materiales', 0);
        $this->assertDatabaseCount('existencias_materiales', 0);
    }

    public function test_plan_agrupa_material_por_pedido_y_preserva_inicial_e_historial(): void
    {
        $pedido = $this->crear();
        (new ExistenciaMaterial)->forceFill(['material_id' => $this->material->id, 'cantidad' => '5'])->save();
        $service = app(PlanificacionMaterialService::class);
        $resumen = $service->disponibilidad($pedido);
        $this->assertCount(1, $resumen);
        $this->assertSame('8.0000', (string) $resumen[0]['necesaria']);
        $this->assertSame('3.0000', (string) $resumen[0]['comprar']);
        $datos = $this->plan($pedido);
        $datos['materiales'][0]['cantidad_planificada'] = '0';
        $this->actingAs($this->maestro)->putJson('/admin/planificacion/'.$pedido->id, $datos)->assertRedirect();
        $this->assertSame('3.0000', $pedido->fresh()->detalles->first()->materiales->sole()->cantidad_inicial);
        $this->assertSame('0.0000', $pedido->fresh()->detalles->first()->materiales->sole()->cantidad_planificada);
        $this->assertSame('5.0000', ExistenciaMaterial::sole()->cantidad);
        $this->assertDatabaseCount('movimientos_materiales', 0);
        $this->putJson('/admin/planificacion/'.$pedido->id, $datos)->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->get('/admin/planificacion/'.$pedido->id.'/edit')->assertOk();
    }

    public function test_plan_rechaza_filas_ajenas_omision_y_unidades_fraccionadas(): void
    {
        $pedido = $this->crear();
        $datos = $this->plan($pedido);
        $datos['materiales'][0]['id'] = 99999;
        $this->putJson('/admin/planificacion/'.$pedido->id, $datos)->assertUnprocessable();
        $datos = $this->plan($pedido);
        array_pop($datos['materiales']);
        $this->putJson('/admin/planificacion/'.$pedido->id, $datos)->assertUnprocessable();
        $pieza = app(CatalogoService::class)->guardar($this->propietario, ['nombre' => 'Pieza aislada', 'categoria_id' => $this->material->categoria_id, 'unidad_id' => UnidadMaterial::where('codigo', 'unidad')->value('id'), 'precio_referencia' => '5', 'activo' => true]);
        $datos = $this->plan($pedido);
        $datos['materiales'][] = ['detalle_pedido_id' => $pedido->detalles->first()->id, 'material_id' => $pieza->id, 'cantidad_planificada' => '1.5'];
        $this->putJson('/admin/planificacion/'.$pedido->id, $datos)->assertUnprocessable();
        $datos['materiales'][2]['cantidad_planificada'] = '2';
        $this->putJson('/admin/planificacion/'.$pedido->id, $datos)->assertRedirect();
        $nueva = MaterialPedido::where('material_id', $pieza->id)->sole();
        $this->assertNull($nueva->cantidad_inicial);
        $this->putJson('/admin/materiales/'.$pieza->id, ['nombre' => $pieza->nombre, 'categoria_id' => $pieza->categoria_id, 'unidad_id' => $this->material->unidad_id, 'precio_referencia' => '5', 'activo' => true])->assertUnprocessable();
        $this->deleteJson('/admin/materiales/'.$pieza->id)->assertUnprocessable();
        $this->assertSame('600.00', $pedido->fresh()->precio_total_acordado);
    }

    public function test_pantallas_y_registros_historicos_se_conservan(): void
    {
        $pedido = $this->crear();
        foreach (['/admin/pedidos', '/admin/pedidos/'.$pedido->id, '/admin/pedidos/'.$pedido->id.'/edit', '/admin/planificacion', '/admin/planificacion/'.$pedido->id, '/admin/planificacion/'.$pedido->id.'/edit'] as $ruta) {
            $this->get($ruta)->assertOk();
        }
        $this->get('/admin/pedidos/create?cotizacion='.$this->cotizacion->id)->assertRedirect('/admin/pedidos/'.$pedido->id);
        $this->deleteJson('/admin/clientes/'.$this->cliente->id)->assertUnprocessable();
        $this->expectException(\LogicException::class);
        EstadoPedido::first()->delete();
    }

    public function test_aceptacion_no_expone_clientes_sin_su_permiso_de_consulta(): void
    {
        $rol = Role::findByName('propietario');
        $rol->revokePermissionTo('clientes.consultar');
        $this->get('/admin/pedidos/create?cotizacion='.$this->cotizacion->id)->assertForbidden();
        $this->postJson('/admin/pedidos/create', $this->datos())->assertForbidden();
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_instalador_incremental_conserva_permisos_personalizados(): void
    {
        $rol = Role::findByName('propietario');
        $rol->revokePermissionTo(['pedidos.crear', 'casos.consultar']);
        $antes = $rol->permissions()->pluck('name')->all();
        $this->seed(FaseCuatroBPermisosSeeder::class);
        $this->assertTrue($rol->fresh()->hasPermissionTo('pedidos.crear'));
        $this->assertFalse($rol->fresh()->hasPermissionTo('casos.consultar'));
        $this->assertCount(count($antes) + 1, $rol->fresh()->permissions);
        $this->seed(FaseCuatroBPermisosSeeder::class);
        $this->assertSame(1, Auditoria::where('accion', 'permiso_agregado_4b')->count());
        $this->assertFalse($this->maestro->fresh()->hasPermissionTo('pedidos.crear'));
    }

    public function test_delegar_creacion_no_concede_programacion_y_autorizacion_se_comprueba_en_servicio(): void
    {
        $id = \Spatie\Permission\Models\Permission::findByName('pedidos.crear')->id;
        app(\App\Services\PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [$id => 'permitir']);
        $datos = $this->datos();
        $datos['responsable_id'] = $this->maestro->id;
        $this->actingAs($this->maestro->fresh())->postJson('/admin/pedidos/create', $datos)->assertForbidden();
        $this->assertDatabaseCount('pedidos', 0);
        $id = \Spatie\Permission\Models\Permission::findByName('pedidos.programar')->id;
        app(\App\Services\PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [$id => 'permitir']);
        $this->actingAs($this->maestro->fresh())->post('/admin/pedidos/create', $datos)->assertRedirect();
        $this->assertSame($this->maestro->id, Pedido::sole()->responsable_id);
    }


    public function test_ficha_cliente_muestra_solo_sus_pedidos_a_propietario_y_maestro(): void
    {
        $pedido = $this->crear();
        $otroCliente = Cliente::create(['nombre' => 'Otro cliente sin pedidos', 'tipo' => 'persona', 'telefono' => '70000001']);
        foreach ([$this->propietario, $this->maestro] as $usuario) {
            $this->actingAs($usuario)->get(route('admin.clientes.show', $this->cliente))
                ->assertOk()->assertSee('Pedidos del cliente')
                ->assertSee(route('admin.pedidos.show', $pedido))
                ->assertViewHas('pedidosCliente', fn ($pedidos) => $pedidos->total() === 1 && $pedidos->first()->id === $pedido->id);
            $this->get(route('admin.clientes.show', $otroCliente))->assertOk()
                ->assertSee('Este cliente todavía no tiene pedidos registrados.')
                ->assertViewHas('pedidosCliente', fn ($pedidos) => $pedidos->total() === 0);
        }
        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_ficha_cliente_no_expone_pedidos_cuando_su_consulta_esta_denegada(): void
    {
        $pedido = $this->crear();
        app(\App\Services\PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [\Spatie\Permission\Models\Permission::findByName('pedidos.consultar')->id => 'denegar']);
        $this->actingAs($this->maestro->fresh())->get(route('admin.clientes.show', $this->cliente))
            ->assertOk()->assertDontSee('Pedidos del cliente')
            ->assertDontSee(route('admin.pedidos.show', $pedido))
            ->assertViewHas('pedidosCliente', null);
    }

    public function test_pedidos_del_cliente_se_paginan_del_mas_reciente_al_mas_antiguo(): void
    {
        $pedido = $this->crear();
        for ($indice = 0; $indice < 8; $indice++) {
            // Copias de filas exclusivamente en SQLite aislado: solo se prueba la consulta paginada.
            $cotizacion = $this->cotizacion->replicate();
            $cotizacion->clave_registro = (string) Str::uuid();
            $cotizacion->save();
            $copia = $pedido->replicate();
            $copia->cotizacion_id = $cotizacion->id;
            $copia->clave_registro = (string) Str::uuid();
            $copia->save();
        }
        $this->get(route('admin.clientes.show', $this->cliente))->assertOk()
            ->assertViewHas('pedidosCliente', fn ($pedidos) => $pedidos->total() === 9 && $pedidos->count() === 8 && $pedidos->first()->id === Pedido::max('id'));
        $this->get(route('admin.clientes.show', [$this->cliente, 'pedidos_pagina' => 2]))->assertOk()
            ->assertViewHas('pedidosCliente', fn ($pedidos) => $pedidos->count() === 1 && $pedidos->first()->id === $pedido->id);
    }
}
