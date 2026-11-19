<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Pedido;
use App\Models\User;
use App\Services\PagoService;
use App\Services\ReporteService;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private User $propietario;

    private User $maestro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->seed(PermisosSeeder::class);
        $this->propietario = User::factory()->create()->assignRole('propietario');
        $this->maestro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($this->propietario);
        $this->travelTo(now()->setDate(2026, 11, 10)->setTime(12, 0));
    }

    private function pedido(string $precio = '1000.00', string $fecha = '2026-11-01'): Pedido
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Ana Mamani']);
        $cotizacion = new Cotizacion;
        $cotizacion->forceFill(['clave_registro' => (string) Str::uuid(), 'solicitud_hash' => str_repeat('a', 64),
            'cliente_id' => $cliente->id, 'responsable_id' => $this->propietario->id,
            'fecha' => $fecha, 'estado' => 'aprobada', 'precio_final' => $precio, 'aprobado_at' => now()])->save();
        $pedido = new Pedido;
        $pedido->forceFill(['cotizacion_id' => $cotizacion->id, 'cliente_id' => $cliente->id,
            'datos_cliente_acordados' => ['nombre' => 'Ana Mamani'], 'fecha_aceptacion' => $fecha,
            'registrado_por_id' => $this->propietario->id, 'precio_total_acordado' => $precio,
            'clave_registro' => (string) Str::uuid(), 'hash_solicitud' => str_repeat('b', 64),
            'estado' => 'aceptado', 'version' => 1])->save();

        return $pedido;
    }

    private function pagar(Pedido $pedido, string $importe, string $fecha, string $medio = 'efectivo'): void
    {
        app(PagoService::class)->guardar($this->propietario, ['pedido_id' => $pedido->id, 'importe' => $importe,
            'fecha' => $fecha, 'medio' => $medio, 'clave_registro' => (string) Str::uuid()]);
    }

    private function reporte(string $tipo, array $filtros = []): array
    {
        return app(ReporteService::class)->generar($this->propietario, $tipo, $filtros);
    }

    public function test_pagos_usan_su_fecha_y_saldos_incluyen_todas_las_fechas_sin_duplicar_pedidos(): void
    {
        $pedido = $this->pedido('1000.00', '2026-10-01');
        $this->pagar($pedido, '100.10', '2026-10-31');
        $this->pagar($pedido, '200.20', '2026-11-01', 'qr');
        $this->pagar($pedido, '300.30', '2026-11-10');
        $pedido->cliente->update(['nombre' => 'Nombre cambiado']);
        $pagos = $this->reporte('pagos');
        $this->assertCount(2, $pagos['filas']);
        $this->assertSame('500,50', $pagos['totales']['Recibido en el período (Bs)']);
        $this->assertSame('Ana Mamani', $pagos['filas'][0][2]);
        $this->assertCount(1, $this->reporte('pagos', ['medio' => 'qr'])['filas']);
        $saldos = $this->reporte('saldos');
        $this->assertCount(1, $saldos['filas']);
        $this->assertSame('600,60', $saldos['totales']['Pagos registrados (Bs)']);
        $this->assertSame('399,40', $saldos['totales']['Saldo actual (Bs)']);
        $this->assertCount(0, $this->reporte('pedidos')['filas']);
    }

    public function test_filtros_saldos_entregados_pagados_y_sin_pagos(): void
    {
        $entregado = $this->pedido('100.00', '2026-01-01');
        $entregado->update(['estado' => 'entregado']);
        $this->pagar($entregado, '100.00', '2026-11-01');
        $this->pedido('25.50');
        $this->assertCount(2, $this->reporte('saldos')['filas']);
        $this->assertSame('100,00', $this->reporte('saldos', ['estado' => 'pagado'])['totales']['Acordado (Bs)']);
        $this->assertSame('25,50', $this->reporte('saldos', ['estado' => 'sin_pagos'])['totales']['Saldo actual (Bs)']);
        $this->assertCount(1, $this->reporte('saldos', ['estado' => 'pendiente'])['filas']);
    }

    public function test_cotizaciones_pendientes_no_son_cero_y_costos_respetan_permiso(): void
    {
        $pedido = $this->pedido('125.00');
        DB::table('cotizaciones')->where('id', $pedido->cotizacion_id)->update(['costo_total' => '100.00']);
        $pendiente = $this->pedido('200.00')->cotizacion;
        DB::table('cotizaciones')->where('id', $pendiente->id)->update(['estado' => 'pendiente_costos', 'aprobado_at' => null, 'precio_final' => null, 'costo_total' => null]);
        $reporte = $this->reporte('cotizaciones');
        $this->assertSame('125,00', $reporte['totales']['Precios aprobados (Bs)']);
        $this->assertSame(1, $reporte['totales']['Sin precio aprobado']);
        $this->assertContains('Pendiente', $reporte['filas'][0]);
        $this->assertContains('Sin aprobar', $reporte['filas'][0]);
        $this->propietario->roles->first()->revokePermissionTo('costos.consultar');
        $reporte = $this->reporte('cotizaciones');
        $this->assertNotContains('Costo estimado Bs', $reporte['columnas']);
        $this->get(route('admin.reportes.show', 'cotizaciones'))->assertOk()->assertDontSee('100,00')->assertDontSee('Costo estimado Bs');
        $this->get(route('admin.reportes.pdf', 'cotizaciones'))->assertOk();
    }

    public function test_fechas_limites_estados_y_totales_de_todas_las_paginas(): void
    {
        for ($i = 0; $i < 23; $i++) {
            $this->pedido('0.10', $i % 2 === 0 ? '2026-11-01' : '2026-11-10');
        }
        $this->pedido('999.00', '2026-10-31');
        $this->pedido('999.00', '2026-11-11');
        $reporte = $this->reporte('pedidos');
        $this->assertCount(23, $reporte['filas']);
        $this->assertSame('2,30', $reporte['totales']['Precios acordados (Bs)']);
        $this->get(route('admin.reportes.show', ['tipo' => 'pedidos', 'page' => 2]))->assertOk()
            ->assertViewHas('filas', fn ($filas) => count($filas) === 3 && $filas->total() === 23)
            ->assertViewHas('reporte', fn ($reporte) => $reporte['totales']['Precios acordados (Bs)'] === '2,30');
        $this->assertCount(0, $this->reporte('pedidos', ['estado' => 'entregado'])['filas']);
    }

    public function test_filtros_incompatibles_invalidos_y_tipo_inexistente_se_rechazan(): void
    {
        foreach ([['pagos', ['medio' => 'transferencia']], ['pagos', ['estado' => 'aprobada']],
            ['pedidos', ['medio' => 'qr']], ['saldos', ['desde' => '2026-11-01']],
            ['cotizaciones', ['desde' => '2026-02-30']], ['cotizaciones', ['desde' => '2026-11-11', 'hasta' => '2026-11-01']],
            ['pedidos', ['estado' => 'aprobada']], ['pagos', ['page' => 0]], ['pagos', ['usuario_id' => 1]]] as [$tipo, $filtros]) {
            foreach (['admin.reportes.show', 'admin.reportes.pdf'] as $ruta) {
                $this->getJson(route($ruta, ['tipo' => $tipo, ...$filtros]))->assertUnprocessable();
            }
        }
        $this->get(route('admin.reportes.show', 'inexistente'))->assertNotFound();
    }

    public function test_denegacion_individual_bloquea_reportes_y_cuenta_inactiva_tampoco_accede(): void
    {
        $this->maestro->givePermissionTo(['reportes.administrativos', 'pagos.consultar']);
        \App\Models\PermisoUsuario::create(['user_id' => $this->maestro->id, 'permission_id' => \Spatie\Permission\Models\Permission::findByName('reportes.administrativos')->id, 'permitido' => false]);
        $this->maestro->unsetRelation('permisosIndividuales');
        $this->actingAs($this->maestro);
        $this->get(route('admin.reportes.index'))->assertForbidden();
        foreach (array_keys(ReporteService::TIPOS) as $tipo) {
            $this->get(route('admin.reportes.show', $tipo))->assertForbidden();
            $this->get(route('admin.reportes.pdf', $tipo))->assertForbidden();
        }
        try {
            app(ReporteService::class)->generar($this->maestro, 'saldos', []);
            $this->fail('El servicio debe impedir el acceso directo.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->propietario->update(['activo' => false]);
        $this->actingAs($this->propietario)->get(route('admin.reportes.index'))->assertRedirect(route('login'));
    }

    public function test_permisos_por_reporte_y_sin_permiso_administrativo(): void
    {
        $rol = $this->propietario->roles->first();
        $rol->revokePermissionTo('pagos.consultar');
        $this->get(route('admin.reportes.index'))->assertOk()->assertDontSee('Consultar saldos')->assertDontSee('Consultar pagos');
        foreach (['pagos', 'saldos'] as $tipo) {
            $this->get(route('admin.reportes.show', $tipo))->assertForbidden();
            $this->get(route('admin.reportes.pdf', $tipo))->assertForbidden();
        }
        $this->get(route('admin.reportes.show', 'pedidos'))->assertOk();
        $rol->revokePermissionTo('reportes.administrativos');
        $this->get(route('admin.reportes.index'))->assertForbidden();
    }

    public function test_consultas_y_pdf_no_escriben_y_no_excluyen_registros_iniciales(): void
    {
        $pedido = $this->pedido();
        $this->pagar($pedido, '25.00', '2026-11-05');
        $antes = $this->snapshot();
        $this->get(route('admin.reportes.index'))->assertOk();
        foreach (array_keys(ReporteService::TIPOS) as $tipo) {
            $this->get(route('admin.reportes.show', $tipo))->assertOk()->assertSee('Registros del sistema');
            $pdf = $this->get(route('admin.reportes.pdf', $tipo))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $pdf->getContent());
            $this->assertStringContainsString('no-store', $pdf->headers->get('Cache-Control'));
        }
        $this->assertSame($antes, $this->snapshot());
        $this->assertSame(1, $this->reporte('pedidos')['totales']['Pedidos']);
    }

    public function test_pdf_rechaza_mas_de_500_sin_truncar_y_pantalla_conserva_total(): void
    {
        $pedido = $this->pedido('0.10');
        $base = $pedido->cotizacion->getAttributes();
        unset($base['id']);
        $registros = [];
        for ($indice = 0; $indice < 500; $indice++) {
            $registros[] = [...$base, 'clave_registro' => (string) Str::uuid()];
        }
        foreach (array_chunk($registros, 50) as $lote) {
            DB::table('cotizaciones')->insert($lote);
        }
        $this->getJson(route('admin.reportes.pdf', 'cotizaciones'))->assertUnprocessable()->assertJsonValidationErrors('pdf');
        $this->get(route('admin.reportes.show', 'cotizaciones'))->assertOk()
            ->assertViewHas('filas', fn ($filas) => $filas->total() === 501 && count($filas) === 20)
            ->assertViewHas('reporte', fn ($reporte) => $reporte['totales']['Precios aprobados (Bs)'] === '50,10');
    }

    public function test_permiso_del_modulo_origen_se_exige_tambien_al_pdf(): void
    {
        $this->propietario->roles->first()->revokePermissionTo('cotizaciones.consultar');
        $this->get(route('admin.reportes.show', 'cotizaciones'))->assertForbidden();
        $this->get(route('admin.reportes.pdf', 'cotizaciones'))->assertForbidden();
        $this->get(route('admin.reportes.show', 'pedidos'))->assertOk();
        $this->get(route('admin.reportes.show', 'pagos'))->assertOk();
    }

    private function snapshot(): array
    {
        $datos = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name") as $tabla) {
            $datos[$tabla->name] = DB::table($tabla->name)->get()->toJson();
        }

        return $datos;
    }

    public function test_maestro_con_delegacion_consulta_reporte_y_pdf_y_pierde_acceso_al_revocar(): void
    {
        $id = \Spatie\Permission\Models\Permission::findByName('reportes.administrativos')->id;
        app(\App\Services\PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [$id => 'permitir']);
        $this->actingAs($this->maestro->fresh())->get(route('admin.reportes.index'))->assertOk();
        $this->get(route('admin.reportes.show', 'cotizaciones'))->assertOk();
        $pdf = $this->get(route('admin.reportes.pdf', 'cotizaciones'))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        app(\App\Services\PermisosUsuarioService::class)->modificar($this->propietario, $this->maestro, [$id => 'denegar']);
        $this->actingAs($this->maestro->fresh())->get(route('admin.reportes.pdf', 'cotizaciones'))->assertForbidden();
    }

}
