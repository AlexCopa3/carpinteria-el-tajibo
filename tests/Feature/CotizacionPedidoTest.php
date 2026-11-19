<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Material;
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
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Services\PermisosUsuarioService;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CotizacionPedidoTest extends TestCase
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

    private function datos($revision): array
    {
        $detalles = [];
        foreach ($revision->detalles as $detalle) {
            $entrada = $detalle->only(['id', 'tipo_mueble_id', 'material_principal_id', 'largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones', 'acabado', 'cantidad_muebles', 'observaciones', 'mano_obra_unitaria', 'mano_obra_total_aplicada', 'motivo_ajuste_mano_obra', 'otros_costos_total', 'precio_propuesto']);
            $entrada['materiales'] = $detalle->materiales->map(fn ($fila) => $fila->only(['id', 'material_id', 'componente', 'cantidad_unitaria', 'cantidad_total_aplicada', 'costo_unitario_estimado', 'estado_costo', 'fuente_costo', 'motivo_ajuste_cantidad']))->all();
            $detalles[] = $entrada;
        }

        return ['version' => $revision->version, 'fecha' => today()->toDateString(), 'recargo_porcentaje' => '20', 'detalles' => $detalles];
    }

    private function aprobar($revision, array $precios)
    {
        return app(CotizacionService::class)->aprobar($this->propietario, $revision, ['version' => $revision->version, 'precios' => $revision->detalles->pluck('id')->combine($precios)->all()]);
    }


    public function test_edicion_directa_conserva_ids_y_el_acuerdo_hasta_confirmar(): void
    {
        $cotizacion = $this->pedido->cotizacion;
        $ids = $cotizacion->detalles->pluck('id')->all();
        $datos = $this->datos($cotizacion);
        $datos['detalles'][0]['mano_obra_unitaria'] = '30';
        $datos['detalles'][0]['mano_obra_total_aplicada'] = null;
        $actual = app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion);
        $this->assertSame($cotizacion->id, $actual->id);
        $this->assertSame($ids, $actual->detalles->pluck('id')->all());
        $this->assertNull($actual->aprobado_at);
        $this->assertSame('600.00', $this->pedido->fresh()->precio_total_acordado);
        $this->assertDatabaseCount('cotizaciones', 1);
        $actual = $this->aprobar($actual, ['350', '300']);
        $entrada = ['version' => $this->pedido->fresh()->version, 'cotizacion_version' => $actual->version, 'aceptacion' => true];
        $pedido = app(PedidoService::class)->aceptarCotizacion($this->propietario, $this->pedido, $entrada);
        $this->assertSame('650.00', $pedido->precio_total_acordado);
        $this->assertSame($actual->version, $pedido->cotizacion_aceptada_version);
        $reenvio = app(PedidoService::class)->aceptarCotizacion($this->propietario, $pedido, $entrada);
        $this->assertSame($pedido->version, $reenvio->version);
        $this->assertDatabaseCount('detalles_cotizacion', 2);
    }

    public function test_maestro_no_puede_confirmar_precio_y_cliente_es_obligatorio(): void
    {
        $this->actingAs($this->maestro)->postJson(route('admin.pedidos.cotizacion.aceptar', $this->pedido), ['version' => $this->pedido->version, 'cotizacion_version' => $this->pedido->cotizacion->version, 'aceptacion' => true])->assertForbidden();
        $this->actingAs($this->propietario)->postJson(route('admin.pedidos.cotizacion.aceptar', $this->pedido), ['version' => $this->pedido->version, 'cotizacion_version' => $this->pedido->cotizacion->version])->assertUnprocessable();
    }

    public function test_consumo_actualiza_la_misma_cotizacion_sin_duplicar(): void
    {
        $fila = $this->pedido->detalles->first()->materiales->first();
        $datos = ['version' => $this->pedido->version, 'materiales' => $this->pedido->detalles->flatMap->materiales->map(fn ($material) => [...$material->only(['id', 'detalle_pedido_id', 'material_id']), 'cantidad_utilizada' => $material->id === $fila->id ? '7' : null])->all()];
        $pedido = app(FabricacionService::class)->guardar($this->propietario, $this->pedido, $datos);
        $cotizacion = $pedido->cotizacion()->first()->load('detalles.materiales');
        $this->assertSame('7.0000', $cotizacion->detalles->first()->materiales->first()->cantidad_total_aplicada);
        $this->assertNull($cotizacion->aprobado_at);
        $this->assertSame('600.00', $pedido->precio_total_acordado);
        $version = $cotizacion->version;
        $datos['version'] = $pedido->version;
        app(FabricacionService::class)->guardar($this->propietario, $pedido, $datos);
        $this->assertSame($version, $cotizacion->fresh()->version);
        $this->assertDatabaseCount('cotizaciones', 1);
        $this->assertDatabaseCount('materiales_cotizacion', 2);
    }

    public function test_vistas_y_edicion_del_pedido_no_crean_otras_cotizaciones(): void
    {
        $this->actingAs($this->propietario)->get(route('admin.pedidos.show', $this->pedido))->assertOk();
        $this->get(route('admin.cotizaciones.edit', $this->pedido->cotizacion_id))->assertOk()->assertSee('Actualizar cotización');
        $this->get(route('admin.cotizaciones.show', $this->pedido->cotizacion_id))->assertOk();
        $this->assertDatabaseCount('cotizaciones', 1);
        $this->assertNotNull($this->pedido->cotizacion->fresh()->aprobado_at);
    }
    public function test_no_confirma_una_cotizacion_sin_aprobar(): void
    {
        $cotizacion = $this->pedido->cotizacion;
        $cotizacion = app(CotizacionService::class)->guardar($this->propietario, $this->datos($cotizacion), $cotizacion);
        $this->actingAs($this->propietario)->postJson(route('admin.pedidos.cotizacion.aceptar', $this->pedido), ['version' => $this->pedido->fresh()->version, 'cotizacion_version' => $cotizacion->version, 'aceptacion' => true])->assertUnprocessable()->assertJsonValidationErrors('cotizacion');
        $this->assertSame('600.00', $this->pedido->fresh()->precio_total_acordado);
    }

    public function test_no_reduce_el_acuerdo_por_debajo_de_los_pagos(): void
    {
        app(PagoService::class)->guardar($this->propietario, ['pedido_id' => $this->pedido->id, 'clave_registro' => (string) Str::uuid(), 'fecha' => today()->toDateString(), 'importe' => '400', 'medio' => 'efectivo']);
        $cotizacion = $this->pedido->cotizacion;
        $cotizacion = app(CotizacionService::class)->guardar($this->propietario, $this->datos($cotizacion), $cotizacion);
        $cotizacion = $this->aprobar($cotizacion, ['100', '100']);
        $this->actingAs($this->propietario)->postJson(route('admin.pedidos.cotizacion.aceptar', $this->pedido), ['version' => $this->pedido->fresh()->version, 'cotizacion_version' => $cotizacion->version, 'aceptacion' => true])->assertUnprocessable()->assertJsonValidationErrors('precios');
        $this->assertSame('600.00', $this->pedido->fresh()->precio_total_acordado);
        $this->assertSame('400.00', $this->pedido->pagos()->sole()->importe);
    }

    public function test_version_vencida_y_cambio_de_diseno_no_reabren_la_aprobacion(): void
    {
        $cotizacion = $this->pedido->cotizacion;
        $datos = $this->datos($cotizacion);
        $datos['version']--;
        try { app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion); $this->fail('Debe rechazar una versión vencida.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('version', $error->errors()); }
        $datos = $this->datos($cotizacion);
        $datos['detalles'][0]['largo_cm'] = '999';
        try { app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion); $this->fail('Debe proteger el diseño aceptado.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('detalles.0', $error->errors()); }
        $this->assertNotNull($cotizacion->fresh()->aprobado_at);
        $this->assertSame('600.00', $cotizacion->fresh()->precio_final);
        $this->assertSame('120.0000', $cotizacion->fresh()->detalles->first()->largo_cm);
    }

    public function test_agregar_mueble_y_aumentar_cantidad_sustituye_el_total_sin_duplicar(): void
    {
        $cotizacion = $this->pedido->cotizacion;
        $datos = $this->datos($cotizacion);
        $datos['detalles'][0]['cantidad_muebles'] = 3;
        $datos['detalles'][0]['mano_obra_total_aplicada'] = null;
        $datos['detalles'][0]['materiales'][0]['cantidad_total_aplicada'] = null;
        $nuevo = $datos['detalles'][1]; unset($nuevo['id']); unset($nuevo['materiales'][0]['id']);
        $datos['detalles'][] = $nuevo;
        $cotizacion = app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion);
        $this->assertSame('9.0000', $cotizacion->detalles->first()->materiales->first()->cantidad_total_aplicada);
        $cotizacion = $this->aprobar($cotizacion, ['450', '300', '200']);
        $pedido = app(PedidoService::class)->aceptarCotizacion($this->propietario, $this->pedido, ['version' => $this->pedido->fresh()->version, 'cotizacion_version' => $cotizacion->version, 'aceptacion' => true]);
        $this->assertSame('950.00', $pedido->precio_total_acordado);
        $this->assertSame(3, $pedido->detalles->first()->cantidad_muebles);
        $this->assertCount(3, $pedido->detalles);
        $this->assertDatabaseCount('cotizaciones', 1);
    }

    public function test_fallo_de_auditoria_revierte_la_edicion_y_conserva_aprobacion(): void
    {
        $cotizacion = $this->pedido->cotizacion;
        $antes = $cotizacion->load('detalles.materiales')->toArray();
        $auditoria = \Mockery::mock(AuditoriaService::class);
        $auditoria->shouldReceive('registrar')->andThrow(new \RuntimeException('Fallo aislado de auditoría'));
        $this->app->instance(AuditoriaService::class, $auditoria);
        try { app(CotizacionService::class)->guardar($this->propietario, $this->datos($cotizacion), $cotizacion); $this->fail('Debe revertir.'); }
        catch (\RuntimeException $error) { $this->assertSame('Fallo aislado de auditoría', $error->getMessage()); }
        $this->assertSame($antes, $cotizacion->fresh()->load('detalles.materiales')->toArray());
        $this->assertSame('600.00', $this->pedido->fresh()->precio_total_acordado);
    }

    private function registrarUsoPrimero(string $cantidad, ?string $motivo = null): Pedido
    {
        $pedido = $this->pedido->fresh();
        $primero = $pedido->detalles->first()->materiales->first()->id;
        $datos = ['version' => $pedido->version, 'motivo_correccion' => $motivo,
            'materiales' => $pedido->detalles->flatMap->materiales->map(fn ($fila) => [...$fila->only(['id', 'detalle_pedido_id', 'material_id']), 'cantidad_utilizada' => $fila->id === $primero ? $cantidad : $fila->cantidad_utilizada])->all()];
        return app(FabricacionService::class)->guardar($this->propietario, $pedido, $datos);
    }

    public function test_quitar_desde_uso_recalcula_sin_borrar_registros_ni_alterar_el_acuerdo(): void
    {
        $this->registrarUsoPrimero('7');
        try { $this->registrarUsoPrimero('0'); $this->fail('Debe pedir motivo al corregir el uso.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('motivo_correccion', $error->errors()); }
        $pedido = $this->registrarUsoPrimero('0', 'El material no se utilizó; se corrige el registro.');
        $cotizacion = $pedido->cotizacion()->first()->load('detalles.materiales');
        $this->assertSame('0.0000', $pedido->detalles->first()->materiales->first()->cantidad_utilizada);
        $this->assertSame('0.00', $cotizacion->detalles->first()->materiales->first()->subtotal_aplicado);
        $this->assertSame('190.00', $cotizacion->costo_total);
        $this->assertSame('600.00', $pedido->precio_total_acordado);
        $this->assertNull($cotizacion->aprobado_at);
        $this->assertNull($cotizacion->precio_sugerido);
        $this->assertDatabaseCount('materiales_pedido', 2);
        $this->assertDatabaseCount('movimientos_materiales', 1);
        $version = $cotizacion->version;
        $this->registrarUsoPrimero('0', 'Reenvío de la misma corrección.');
        $this->assertSame($version, $cotizacion->fresh()->version);
    }

    public function test_quitar_desde_cotizacion_corrige_uso_con_motivo_y_recalcula_el_precio_sugerido(): void
    {
        $this->registrarUsoPrimero('7');
        $cotizacion = $this->pedido->cotizacion()->first();
        $datos = $this->datos($cotizacion);
        $datos['detalles'][0]['materiales'][0]['retirar'] = true;
        $datos['detalles'][0]['materiales'][0]['cantidad_unitaria'] = '999';
        try { app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion); $this->fail('Debe pedir motivo.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('detalles.0.materiales.0.motivo_retiro', $error->errors()); }
        $this->assertSame('7.0000', $this->pedido->detalles->first()->materiales->first()->fresh()->cantidad_utilizada);
        $datos['detalles'][0]['materiales'][0]['motivo_retiro'] = 'Registro equivocado: este material no fue utilizado.';
        $actual = app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion);
        $this->assertSame($cotizacion->id, $actual->id);
        $this->assertSame('0.0000', $actual->detalles->first()->materiales->first()->cantidad_total_aplicada);
        $this->assertSame('0.0000', $this->pedido->detalles->first()->materiales->first()->fresh()->cantidad_utilizada);
        $this->assertSame('190.00', $actual->costo_total);
        $this->assertSame('228.00', $actual->precio_sugerido);
        $this->assertSame('600.00', $this->pedido->fresh()->precio_total_acordado);
        $this->assertDatabaseHas('auditorias', ['accion' => 'material_retirado_desde_cotizacion', 'usuario_id' => $this->propietario->id]);
        $actual = $this->aprobar($actual, ['54', '174']);
        $pedido = app(PedidoService::class)->aceptarCotizacion($this->propietario, $this->pedido, ['version' => $this->pedido->fresh()->version, 'cotizacion_version' => $actual->version, 'aceptacion' => true]);
        $this->assertSame('228.00', $pedido->precio_total_acordado);
        $this->assertSame('0.0000', $pedido->detalles->first()->materiales->first()->cantidad_planificada);
        $this->assertDatabaseCount('materiales_cotizacion', 2);
        $this->assertDatabaseCount('cotizaciones', 1);
    }

    public function test_no_se_elude_el_permiso_de_corregir_consumo_desde_cotizacion(): void
    {
        $this->registrarUsoPrimero('7');
        app(PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [Permission::findByName('planificacion.modificar')->id => 'denegar']);
        $cotizacion = $this->pedido->cotizacion()->first();
        $datos = $this->datos($cotizacion); unset($datos['recargo_porcentaje']);
        $datos['detalles'][0]['materiales'][0]['retirar'] = true;
        $datos['detalles'][0]['materiales'][0]['motivo_retiro'] = 'Solicitud sin permiso efectivo de fabricación.';
        try { app(CotizacionService::class)->guardar($this->maestro, $datos, $cotizacion); $this->fail('Debe denegar el retiro.'); }
        catch (\Illuminate\Auth\Access\AuthorizationException $error) { $this->assertNotEmpty($error->getMessage()); }
        $this->assertSame('7.0000', $this->pedido->detalles->first()->materiales->first()->fresh()->cantidad_utilizada);
        $this->assertSame($cotizacion->version, $cotizacion->fresh()->version);
    }

    public function test_un_costo_desconocido_retirado_no_se_convierte_en_costo_unitario_cero(): void
    {
        $cotizacion = $this->pedido->cotizacion;
        $datos = $this->datos($cotizacion);
        $datos['detalles'][0]['materiales'][0]['retirar'] = true;
        $datos['detalles'][0]['materiales'][0]['estado_costo'] = 'pendiente';
        $datos['detalles'][0]['materiales'][0]['costo_unitario_estimado'] = null;
        $actual = app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion);
        $fila = $actual->detalles->first()->materiales->first();
        $this->assertNull($fila->costo_unitario_estimado);
        $this->assertSame('pendiente', $fila->estado_costo);
        $this->assertSame('0.00', $fila->subtotal_aplicado);
        $datos = $this->datos($actual);
        $datos['detalles'][0]['materiales'][0]['cantidad_unitaria'] = '1';
        $datos['detalles'][0]['materiales'][0]['cantidad_total_aplicada'] = '2';
        $actual = app(CotizacionService::class)->guardar($this->propietario, $datos, $actual);
        $this->assertNull($actual->costo_total);
        try { $this->aprobar($actual, ['300', '300']); $this->fail('Debe bloquear la aprobación del costo desconocido.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('precios', $error->errors()); }
    }

    public function test_retiro_con_id_de_otro_mueble_se_rechaza_y_revierte(): void
    {
        $cotizacion = $this->pedido->cotizacion;
        $datos = $this->datos($cotizacion);
        $datos['detalles'][0]['materiales'][0]['id'] = $cotizacion->detalles->last()->materiales->first()->id;
        $datos['detalles'][0]['materiales'][0]['retirar'] = true;
        // Evitar un duplicado para comprobar específicamente pertenencia al mueble.
        unset($datos['detalles'][1]['materiales'][0]['id']);
        try { app(CotizacionService::class)->guardar($this->propietario, $datos, $cotizacion); $this->fail('Debe rechazar la referencia ajena.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey('detalles.0.materiales.0.retirar', $error->errors()); }
        $this->assertNotNull($cotizacion->fresh()->aprobado_at);
        $this->assertNull($this->pedido->detalles->first()->materiales->first()->fresh()->cantidad_utilizada);
    }

}
