<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->travelTo(now()->setDate(2026, 11, 5)->startOfDay());
        $this->seed(PermisosSeeder::class);
        $this->usuario = User::factory()->create()->assignRole('propietario');
        $this->actingAs($this->usuario);
    }

    private function cotizacion(string $fecha, string $estado = 'borrador'): int
    {
        return DB::table('cotizaciones')->insertGetId([
            'clave_registro' => (string) Str::uuid(), 'solicitud_hash' => str_repeat('a', 64),
            'responsable_id' => $this->usuario->id, 'fecha' => $fecha, 'estado' => $estado,
            'costo_total' => '123456.78', 'recargo_porcentaje' => '43.21',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function pedido(string $estado, ?string $entrega): int
    {
        $cliente = DB::table('clientes')->insertGetId(['nombre' => 'Contacto privado', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => true]);

        return DB::table('pedidos')->insertGetId([
            'cotizacion_id' => $this->cotizacion('2026-11-05', 'aprobada'), 'cliente_id' => $cliente,
            'datos_cliente_acordados' => json_encode(['nombre' => 'Contacto privado']),
            'fecha_aceptacion' => '2026-11-05', 'fecha_prevista_entrega' => $entrega,
            'registrado_por_id' => $this->usuario->id, 'estado' => $estado,
            'precio_total_acordado' => '654321.98', 'clave_registro' => (string) Str::uuid(),
            'hash_solicitud' => str_repeat('b', 64), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_cuenta_mes_y_pendientes_sin_incluir_otro_mes_en_el_total_mensual(): void
    {
        $this->cotizacion('2026-10-31');
        $this->cotizacion('2026-11-01');
        $this->cotizacion('2026-11-30', 'pendiente_costos');
        $this->cotizacion('2026-12-01', 'aprobada');
        $this->get('/admin')->assertOk()->assertViewHas('indicadores', fn ($datos) => $datos['cotizaciones'] === 2 && $datos['cotizaciones_pendientes'] === 3);
    }

    public function test_tablero_separa_estados_y_prioriza_entregas_en_pedidos_abiertos(): void
    {
        $sinFecha = $this->pedido('aceptado', null);
        $lejano = $this->pedido('en_fabricacion', '2026-11-25');
        $cercano = $this->pedido('aceptado', '2026-11-06');
        $terminado = $this->pedido('terminado', '2026-11-05');
        $entregado = $this->pedido('entregado', '2026-11-05');
        $response = $this->get('/admin')->assertOk();
        $response->assertViewHas('indicadores', fn ($datos) => $datos['pedidos_aceptados'] === 2 && $datos['pedidos_fabricacion'] === 1);
        $response->assertViewHas('tableroPedidos', fn ($tablero) => $tablero['aceptado']['pedidos']->pluck('id')->all() === [$cercano, $sinFecha]
            && $tablero['en_fabricacion']['pedidos']->pluck('id')->all() === [$lejano]
            && $tablero['terminado']['pedidos']->pluck('id')->all() === [$terminado]
            && $tablero['entregado']['pedidos']->pluck('id')->all() === [$entregado]
        );
    }

    public function test_listas_limitadas_y_sin_importes_datos_personales_ni_margenes(): void
    {
        for ($indice = 0; $indice < 7; $indice++) {
            $this->pedido('aceptado', null);
        }
        $maestro = User::factory()->create()->assignRole('maestro');
        $response = $this->actingAs($maestro)->get('/admin')->assertOk();
        $response->assertViewHas('cotizacionesRecientes', fn ($filas) => $filas->count() === 5 && array_keys($filas->first()->getAttributes()) === ['id', 'fecha', 'estado']);
        $response->assertViewHas('tableroPedidos', fn ($tablero) => $tablero['aceptado']['total'] === 7
            && $tablero['aceptado']['pedidos']->count() === 5
            && array_keys($tablero['aceptado']['pedidos']->first()->getAttributes()) === ['id', 'estado', 'fecha_prevista_entrega']);
        $response->assertSee('Ver los 7 pedidos')->assertSee('Distribución de 7 pedidos por estado.');
        $response->assertDontSee('123456.78')->assertDontSee('654321.98')->assertDontSee('43.21')->assertDontSee('Contacto privado')->assertDontSee('Resumen administrativo');
    }

    public function test_sin_permiso_no_consulta_ni_muestra_los_modulos(): void
    {
        Role::findByName('maestro')->revokePermissionTo(['cotizaciones.consultar', 'pedidos.consultar', 'clientes.consultar', 'materiales.consultar']);
        $maestro = User::factory()->create()->assignRole('maestro');
        $this->actingAs($maestro);
        $consultas = [];
        DB::listen(function ($query) use (&$consultas) {
            $consultas[] = $query->sql;
        });
        $this->get('/admin')->assertOk()->assertViewHas('indicadores', [])->assertDontSee('Cotizaciones recientes')->assertDontSee('Tablero de pedidos')->assertViewHas('tableroPedidos', [])->assertDontSee('Estado de los pedidos');
        foreach ($consultas as $consulta) {
            $this->assertDoesNotMatchRegularExpression('/from\s+["`]?\b(cotizaciones|pedidos|clientes|materiales)\b/i', $consulta);
        }
    }

    public function test_panel_vacio_no_inventa_valores_y_el_visitante_no_accede(): void
    {
        $this->get('/admin')->assertOk()->assertSee('Aún no hay cotizaciones')->assertSee('No hay pedidos en este estado.')->assertSee('Distribución de 0 pedidos por estado.')
            ->assertViewHas('indicadores', fn ($datos) => $datos['cotizaciones'] === 0 && $datos['pedidos_aceptados'] === 0);
        auth()->logout();
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_cerrados_limitados_y_ordenados_por_actualizacion_sin_cambiar_datos(): void
    {
        $ids = [];
        for ($indice = 0; $indice < 7; $indice++) {
            $ids[] = $this->pedido('entregado', '2026-11-05');
        }
        $prioritario = $ids[0];
        DB::table('pedidos')->where('id', $prioritario)->update(['updated_at' => now()->addHour()]);
        $antes = DB::table('pedidos')->orderBy('id')->get()->toJson();

        $this->get('/admin')->assertOk()->assertViewHas('tableroPedidos', fn ($tablero) => $tablero['entregado']['total'] === 7
            && $tablero['entregado']['pedidos']->pluck('id')->all() === [$prioritario, $ids[6], $ids[5], $ids[4], $ids[3]]
            && $tablero['terminado']['total'] === 0
            && $tablero['terminado']['pedidos']->isEmpty()
        );
        $this->assertSame($antes, DB::table('pedidos')->orderBy('id')->get()->toJson());
    }
}
