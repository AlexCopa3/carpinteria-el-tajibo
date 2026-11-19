<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\PagoService;
use Database\Seeders\PermisosSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagosTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $maestro;

    private Pedido $pedido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed(PermisosSeeder::class);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $this->pedido = $this->pedido('1500.00');
        $this->actingAs($this->propietario);
    }

    private function pedido(string $precio): Pedido
    {
        $cliente = Cliente::factory()->create();
        $cotizacion = new Cotizacion;
        $cotizacion->forceFill(['clave_registro' => (string) Str::uuid(), 'solicitud_hash' => str_repeat('a', 64),
            'cliente_id' => $cliente->id, 'responsable_id' => $this->propietario->id,
            'fecha' => today(), 'estado' => 'aprobada', 'precio_final' => $precio, 'aprobado_at' => now()])->save();
        $pedido = new Pedido;
        $pedido->forceFill(['cotizacion_id' => $cotizacion->id, 'cliente_id' => $cliente->id,
            'datos_cliente_acordados' => ['nombre' => $cliente->nombre, 'telefono' => $cliente->telefono],
            'fecha_aceptacion' => today(), 'registrado_por_id' => $this->propietario->id,
            'precio_total_acordado' => $precio, 'clave_registro' => (string) Str::uuid(),
            'hash_solicitud' => str_repeat('b', 64), 'estado' => 'aceptado', 'version' => 1])->save();

        return $pedido;
    }

    private function datos(array $cambios = []): array
    {
        return ['pedido_id' => $this->pedido->id, 'fecha' => today()->format('Y-m-d'),
            'importe' => '500.00', 'medio' => 'efectivo', 'clave_registro' => (string) Str::uuid(), ...$cambios];
    }

    private function editar(Pago $pago, array $cambios = []): array
    {
        return ['pedido_id' => $pago->pedido_id, 'fecha' => $pago->fecha->format('Y-m-d'), 'importe' => $pago->importe,
            'medio' => $pago->medio, 'referencia' => $pago->referencia, 'observaciones' => $pago->observaciones,
            'version' => $pago->version, 'motivo' => 'Corrección del registro recibido', ...$cambios];
    }

    public function test_anticipo_parcial_y_total_suman_exactamente_sin_cambiar_pedido(): void
    {
        $antes = $this->pedido->fresh()->getAttributes();
        $service = app(PagoService::class);
        foreach (['500.00', '700.00', '300.00'] as $importe) {
            $service->guardar($this->propietario, $this->datos(['importe' => $importe]));
        }
        $this->assertSame(['precio' => '1500.00', 'pagado' => '1500.00', 'saldo' => '0.00', 'estado' => 'Pagado'], $service->resumen($this->propietario, $this->pedido));
        $this->assertSame($antes, $this->pedido->fresh()->getAttributes());
        $this->assertDatabaseCount('pagos', 3);
        $this->get(route('admin.pagos.index', ['pedido_id' => $this->pedido->id]))->assertOk()->assertSee('Saldo pendiente Bs')->assertSee('Pagado');
    }

    public function test_decimal_en_centavos_no_acumula_error_float_y_sin_pagos_no_se_inventan(): void
    {
        $service = app(PagoService::class);
        $this->assertSame('Sin pagos', $service->resumen($this->propietario, $this->pedido)['estado']);
        for ($i = 0; $i < 10; $i++) {
            $service->guardar($this->propietario, $this->datos(['importe' => '0.10']));
        }
        $this->assertSame('1.00', $service->resumen($this->propietario, $this->pedido)['pagado']);
        $this->assertSame('1499.00', $service->resumen($this->propietario, $this->pedido)['saldo']);
    }

    public function test_reenvio_identico_no_duplica_y_uuid_con_datos_distintos_se_rechaza(): void
    {
        $datos = $this->datos();
        $service = app(PagoService::class);
        $pago = $service->guardar($this->propietario, $datos);
        $this->assertSame($pago->id, $service->guardar($this->propietario, $datos)->id);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertSame(1, Auditoria::where('registro_type', Pago::class)->count());
        $this->postJson(route('admin.pagos.store'), [...$datos, 'importe' => '600'])->assertUnprocessable();
    }

    public function test_efectivo_qr_vistas_y_registro_http_guardan_actor_de_sesion(): void
    {
        foreach (['efectivo', 'qr'] as $medio) {
            $this->post(route('admin.pagos.store'), $this->datos(['medio' => $medio]))->assertRedirect();
        }
        $pago = Pago::latest('id')->first();
        $this->assertSame($this->propietario->id, $pago->registrado_por_id);
        $this->get(route('admin.pagos.show', $pago))->assertOk()->assertSee('Responsables del registro')->assertSee('QR');
        $this->get(route('admin.pagos.edit', $pago))->assertOk()->assertSee('Motivo de la corrección');
        $this->get(route('admin.pagos.create', ['pedido_id' => $this->pedido->id]))->assertOk();
    }

    public function test_entrada_invalida_sobrepago_y_campos_administrativos_se_rechazan(): void
    {
        foreach ([
            ['importe' => '0'], ['importe' => '-1'], ['importe' => '1.001'], ['importe' => '1e3'], ['importe' => '1500.01'],
            ['importe' => '1,50'], ['medio' => 'transferencia'], ['fecha' => today()->addDay()->format('Y-m-d')],
            ['pedido_id' => 99999], ['saldo' => '0'], ['registrado_por_id' => $this->maestro->id], ['precio_total_acordado' => '1'],
        ] as $cambio) {
            $this->postJson(route('admin.pagos.store'), $this->datos($cambio))->assertUnprocessable();
        }
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_edicion_excluye_importe_anterior_y_conserva_registrador_e_historial(): void
    {
        $service = app(PagoService::class);
        $pago = $service->guardar($this->propietario, $this->datos(['importe' => '1500']));
        $this->put(route('admin.pagos.update', $pago), $this->editar($pago, ['importe' => '1200', 'medio' => 'qr', 'referencia' => 'Referencia QR']))->assertRedirect();
        $this->assertSame('300.00', $service->resumen($this->propietario, $this->pedido)['saldo']);
        $this->assertSame($this->propietario->id, $pago->fresh()->registrado_por_id);
        $auditoria = Auditoria::where('registro_type', Pago::class)->where('accion', 'modificado')->sole();
        $this->assertSame('1500.00', $auditoria->datos_anteriores['importe']);
        $this->assertSame('1200.00', $auditoria->datos_nuevos['importe']);
        $this->assertSame(2, $pago->fresh()->version);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_edicion_obsoleta_sobrepago_o_motivo_vacio_no_modifica(): void
    {
        $service = app(PagoService::class);
        $pago = $service->guardar($this->propietario, $this->datos());
        $service->guardar($this->propietario, $this->editar($pago, ['importe' => '600']), $pago);
        $this->putJson(route('admin.pagos.update', $pago), $this->editar($pago, ['importe' => '700']))->assertUnprocessable()->assertJsonValidationErrors('version');
        $pago->refresh();
        $this->putJson(route('admin.pagos.update', $pago), $this->editar($pago, ['importe' => '1501']))->assertUnprocessable();
        $this->putJson(route('admin.pagos.update', $pago), $this->editar($pago, ['motivo' => '    ']))->assertUnprocessable();
        $this->assertSame('600.00', $pago->fresh()->importe);
    }

    public function test_reasignar_pedido_recalcula_ambos_y_rechaza_destino_sin_saldo(): void
    {
        $service = app(PagoService::class);
        $destino = $this->pedido('800');
        $pago = $service->guardar($this->propietario, $this->datos());
        $service->guardar($this->propietario, $this->editar($pago, ['pedido_id' => $destino->id]), $pago);
        $this->assertSame('1500.00', $service->resumen($this->propietario, $this->pedido)['saldo']);
        $this->assertSame('300.00', $service->resumen($this->propietario, $destino)['saldo']);
        $pago2 = $service->guardar($this->propietario, $this->datos(['importe' => '400']));
        $this->putJson(route('admin.pagos.update', $pago2), $this->editar($pago2, ['pedido_id' => $destino->id]))->assertUnprocessable();
        $this->assertSame($this->pedido->id, $pago2->fresh()->pedido_id);
    }

    public function test_repetir_alta_despues_de_editar_no_revierte_la_correccion(): void
    {
        $service = app(PagoService::class);
        $datos = $this->datos();
        $pago = $service->guardar($this->propietario, $datos);
        $service->guardar($this->propietario, $this->editar($pago, ['importe' => '600']), $pago);
        $this->assertSame('600.00', $service->guardar($this->propietario, $datos)->importe);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_denegacion_individual_bloquea_pagos_y_oculta_datos_en_pedido(): void
    {
        $pago = app(PagoService::class)->guardar($this->propietario, $this->datos());
        $this->maestro->givePermissionTo(['pagos.consultar', 'pagos.gestionar']);
        \App\Models\PermisoUsuario::create(['user_id' => $this->maestro->id, 'permission_id' => \Spatie\Permission\Models\Permission::findByName('pagos.consultar')->id, 'permitido' => false]);
        $this->maestro->unsetRelation('permisosIndividuales');
        $this->actingAs($this->maestro);
        foreach (['admin.pagos.index', 'admin.pagos.create', 'admin.pagos.show', 'admin.pagos.edit'] as $ruta) {
            $this->get(route($ruta, in_array($ruta, ['admin.pagos.show', 'admin.pagos.edit']) ? $pago : []))->assertForbidden();
        }
        $this->postJson(route('admin.pagos.store'), $this->datos())->assertForbidden();
        $this->putJson(route('admin.pagos.update', $pago), $this->editar($pago))->assertForbidden();
        $this->get(route('admin.pedidos.show', $this->pedido))->assertOk()->assertDontSee('Pagos del pedido')->assertDontSee('Saldo según registros')->assertDontSee('Ver pagos');
        $this->expectException(AuthorizationException::class);
        app(PagoService::class)->resumen($this->maestro, $this->pedido);
    }

    public function test_usuario_inactivo_y_propietario_sin_permiso_gestion_no_escribe(): void
    {
        $this->propietario->roles->first()->revokePermissionTo('pagos.gestionar');
        $this->get(route('admin.pagos.index'))->assertOk();
        $this->postJson(route('admin.pagos.store'), $this->datos())->assertForbidden();
        $this->propietario->update(['activo' => false]);
        $this->get(route('admin.pagos.index'))->assertRedirect();
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_no_hay_eliminacion_y_fallo_auditoria_revierte_creacion_y_edicion(): void
    {
        $pago = app(PagoService::class)->guardar($this->propietario, $this->datos());
        $this->delete(route('admin.pagos.show', $pago))->assertStatus(405);
        $this->mock(AuditoriaService::class)->shouldReceive('registrar')->andThrow(new \RuntimeException('Fallo QA'));
        foreach ([null, $pago] as $registro) {
            try {
                app(PagoService::class)->guardar($this->propietario, $registro ? $this->editar($pago, ['importe' => '600']) : $this->datos(), $registro);
                $this->fail('Faltó rollback');
            } catch (\RuntimeException $error) {
                $this->assertSame('Fallo QA', $error->getMessage());
            }
        }
        $this->assertDatabaseCount('pagos', 1);
        $this->assertSame('500.00', $pago->fresh()->importe);
    }

    public function test_entrega_con_saldo_advierte_sin_bloquear_y_edicion_no_reabre_entregado(): void
    {
        $this->pedido->forceFill(['estado' => 'terminado', 'materiales_validados_por_id' => $this->propietario->id, 'materiales_validados_at' => now(), 'precio_confirmado_por_id' => $this->propietario->id, 'precio_confirmado_at' => now()])->save();
        $this->get(route('admin.pedidos.show', $this->pedido))->assertOk()->assertSee('Saldo pendiente según pagos registrados')->assertSee('Registrar entrega');
        $datos = ['version' => $this->pedido->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true];
        $this->post(route('admin.pedidos.entregar', $this->pedido), $datos)->assertRedirect();
        $this->assertSame('entregado', $this->pedido->fresh()->estado);
        $service = app(PagoService::class);
        $pago = $service->guardar($this->propietario, $this->datos(['importe' => '1500']));
        $service->guardar($this->propietario, $this->editar($pago, ['importe' => '1000']), $pago);
        $this->assertSame('500.00', $service->resumen($this->propietario, $this->pedido)['saldo']);
        $this->assertSame('entregado', $this->pedido->fresh()->estado);
    }

    public function test_maestro_con_delegacion_registra_y_edita_pago_con_auditoria_y_sin_sobrepago(): void
    {
        $reglas = [];
        foreach (['pagos.consultar', 'pagos.gestionar'] as $permiso) {
            $reglas[\Spatie\Permission\Models\Permission::findByName($permiso)->id] = 'permitir';
        }
        app(\App\Services\PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, $reglas);
        $this->actingAs($this->maestro->fresh())->post(route('admin.pagos.store'), $this->datos())->assertRedirect();
        $pago = Pago::sole();
        $this->assertSame($this->maestro->id, $pago->registrado_por_id);
        $this->put(route('admin.pagos.update', $pago), $this->editar($pago, ['importe' => '600.00']))->assertRedirect();
        $this->assertSame('600.00', $pago->fresh()->importe);
        $this->assertSame(2, $pago->auditorias()->count());
        $this->postJson(route('admin.pagos.store'), $this->datos(['importe' => '1000.00']))->assertUnprocessable();
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_listado_por_pedido_incluye_trabajos_sin_pagos_y_saldos_exactos(): void
    {
        $otro = $this->pedido('800.00');
        app(PagoService::class)->guardar($this->propietario, $this->datos());
        $respuesta = $this->get(route('admin.pagos.index'))->assertOk();
        $respuesta->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 2);
        $respuesta->assertViewHas('resumenes', fn ($resumenes) => $resumenes[$this->pedido->id]['saldo'] === '1000.00' && $resumenes[$otro->id]['pagado'] === '0.00' && $resumenes[$otro->id]['saldo'] === '800.00');
        $respuesta->assertSee(route('admin.pagos.pedido', $otro), false);
        $nombreAcordado = $this->pedido->datos_cliente_acordados['nombre'];
        $this->pedido->cliente->update(['nombre' => 'Cliente con nombre actualizado QA']);
        $this->get(route('admin.pagos.index', ['q' => $nombreAcordado]))->assertOk()->assertViewHas('pedidos', fn ($pedidos) => $pedidos->contains('id', $this->pedido->id));
        $this->get(route('admin.pagos.index', ['q' => (string) $otro->id]))->assertOk()->assertViewHas('pedidos', fn ($pedidos) => $pedidos->total() === 1 && $pedidos->first()->id === $otro->id);
        $this->get(route('admin.pagos.index', ['q' => 'Cliente inexistente QA 987654321']))->assertOk()->assertViewHas('pedidos', fn ($pedidos) => $pedidos->isEmpty());
    }

    public function test_ver_pagos_del_pedido_muestra_solo_su_historial_y_funciona_sin_registros(): void
    {
        $otro = $this->pedido('800.00');
        $pago = app(PagoService::class)->guardar($this->propietario, $this->datos());
        $this->get(route('admin.pagos.pedido', $this->pedido))->assertOk()
            ->assertSee('Pagos registrados')->assertSee('Registrar pago')
            ->assertViewHas('pagos', fn ($pagos) => $pagos->total() === 1 && $pagos->first()->id === $pago->id);
        $this->get(route('admin.pagos.pedido', $otro))->assertOk()->assertSee('Aún no hay pagos registrados')
            ->assertViewHas('resumen', fn ($resumen) => $resumen['saldo'] === '800.00')
            ->assertViewHas('pagos', fn ($pagos) => $pagos->isEmpty());
        $this->get(route('admin.pagos.pedido', 99999))->assertNotFound();
    }

    public function test_registrar_y_editar_desde_la_ficha_regresan_a_los_pagos_del_pedido(): void
    {
        $this->post(route('admin.pagos.store', ['desde' => 'pagos']), $this->datos())->assertRedirect(route('admin.pagos.pedido', $this->pedido));
        $pago = Pago::sole();
        $this->put(route('admin.pagos.update', [$pago, 'desde' => 'pagos']), $this->editar($pago, ['importe' => '600']))->assertRedirect(route('admin.pagos.pedido', $this->pedido));
        $this->assertSame('900.00', app(PagoService::class)->resumen($this->propietario, $this->pedido)['saldo']);
        $this->get(route('admin.pagos.edit', [$pago, 'desde' => 'pagos']))->assertOk()->assertSee(route('admin.pagos.pedido', $this->pedido), false);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_ficha_de_pagos_respeta_consulta_gestion_y_pago_completo(): void
    {
        $this->actingAs($this->maestro)->get(route('admin.pagos.pedido', $this->pedido))->assertForbidden();
        $this->actingAs($this->propietario);
        $this->propietario->roles->first()->revokePermissionTo('pagos.gestionar');
        $this->get(route('admin.pagos.pedido', $this->pedido))->assertOk()->assertDontSee('Registrar pago');
        $this->propietario->roles->first()->givePermissionTo('pagos.gestionar');
        app(PagoService::class)->guardar($this->propietario, $this->datos(['importe' => '1500']));
        $this->get(route('admin.pagos.pedido', $this->pedido))->assertOk()->assertSee('Pagado')->assertDontSee('Registrar pago');
    }

}
