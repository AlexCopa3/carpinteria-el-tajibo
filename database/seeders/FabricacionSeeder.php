<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\Pedido;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CierrePedidoService;
use App\Services\CotizacionService;
use App\Services\FabricacionService;
use App\Services\PedidoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FabricacionSeeder extends Seeder
{
    public function run(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $propietario = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'datos_fabricacion_cargados')->exists()) {
                return;
            }
            $ids = Auditoria::where('accion', 'datos_ejemplo_cargados')->get()->flatMap(fn ($auditoria) => $auditoria->datos_nuevos['ids']['pedidos'] ?? [])->all();
            $pedidos = Pedido::whereIn('id', $ids)->where('estado', 'en_fabricacion')->orderByDesc('id')->limit(2)->get();
            if ($pedidos->count() !== 2) {
                throw new \RuntimeException('Faltan pedidos iniciales en fabricación para preparar el cierre.');
            }
            foreach ($pedidos as $indice => $pedido) {
                $this->cerrar($propietario, $pedido, $indice === 1);
            }
            app(AuditoriaService::class)->registrar(null, $propietario, 'datos_fabricacion_cargados', [], ['ids' => $pedidos->pluck('id')->all(), 'origen' => 'simulado', 'fuente' => 'FabricacionSeeder', 'alcance' => 'Datos ficticios; sin validación humana de trabajos reales.']);
        });
    }

    public function cerrar(User $propietario, Pedido $pedido, bool $entregar): void
    {
        $precios = $pedido->cotizacion->detalles->pluck('precio_final', 'id')->all();
        $filas = $pedido->detalles->flatMap->materiales->map(fn ($material) => [
            'id' => $material->id, 'detalle_pedido_id' => $material->detalle_pedido_id,
            'material_id' => $material->material_id, 'cantidad_utilizada' => $material->cantidad_planificada,
            'observaciones_uso' => 'Material utilizado durante la fabricación.',
        ])->values()->all();
        $pedido = app(FabricacionService::class)->guardar($propietario, $pedido, ['version' => $pedido->version, 'materiales' => $filas])->fresh();
        $cotizacion = $pedido->cotizacion;
        if ($cotizacion->estado !== 'aprobada') {
            $cotizacion = app(CotizacionService::class)->aprobar($propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => $cotizacion->detalles->mapWithKeys(fn ($detalle) => [$detalle->id => $precios[$detalle->id] ?? $detalle->precio_sugerido])->all()]);
        }
        $pedido = app(PedidoService::class)->aceptarCotizacion($propietario, $pedido, ['version' => $pedido->version, 'cotizacion_version' => $cotizacion->version, 'aceptacion' => true]);
        $pedido = app(CierrePedidoService::class)->terminar($propietario, $pedido, ['version' => $pedido->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true, 'confirmar_precio' => true, 'validar_materiales' => true]);
        if ($entregar) {
            app(CierrePedidoService::class)->entregar($propietario, $pedido, ['version' => $pedido->version, 'clave_operacion' => (string) Str::uuid(), 'confirmacion' => true]);
        }
    }
}
