<?php

namespace Database\Seeders;

use App\Services\PedidoService;
use App\Services\PlanificacionMaterialService;
use Illuminate\Database\Seeder;

class PedidosSeeder extends Seeder
{
    public function run(array &$carga = []): void
    {
        if (! isset($carga['lote'], $carga['propietario'])) {
            throw new \RuntimeException('Ejecuta DatosSistemaSeeder para respetar el orden y el control de duplicados.');
        }
        $propietario = $carga['propietario'];
        $clientes = $carga['clientes'];
        foreach ($carga['cotizaciones'] as $indice => $cotizacion) {
            if ($carga['lote'] === DatosSistemaSeeder::PRIMERA_CARGA) {
                if ($indice >= 5) {
                    $pedido = app(PedidoService::class)->crear($propietario, ['cotizacion_id' => $cotizacion->id, 'clave_registro' => fake()->uuid(), 'aceptacion' => true, 'cliente_id' => $clientes[$indice]->id, 'observaciones' => 'Coordinar entrega con el cliente.']);
                    if ($indice === 7) {
                        $plan = ['version' => $pedido->version, 'motivo' => 'Aprovechamiento conjunto del material.', 'materiales' => $pedido->detalles->flatMap->materiales->map(fn ($fila) => $fila->only(['id', 'detalle_pedido_id', 'material_id', 'cantidad_planificada', 'observaciones']))->values()->all()];
                        $plan['materiales'][0]['cantidad_planificada'] = '5';
                        $pedido = app(PlanificacionMaterialService::class)->guardar($propietario, $pedido, $plan);
                    }
                    if ($indice >= 6) {
                        app(PedidoService::class)->cambiarEstado($propietario, $pedido, ['version' => $pedido->version, 'estado' => 'en_fabricacion', 'clave_operacion' => fake()->uuid()]);
                    }
                }
            } else {
                if ($indice >= 10) {
                    $pedido = app(PedidoService::class)->crear($propietario, ['cotizacion_id' => $cotizacion->id, 'clave_registro' => fake()->uuid(), 'aceptacion' => true, 'cliente_id' => $clientes[$indice]->id, 'fecha_prevista_entrega' => today()->addDays(7 + ($indice % 5) * 3)->format('Y-m-d'), 'observaciones' => 'Coordinar horario de entrega con el cliente.']);
                    if ($indice >= 13) {
                        app(PedidoService::class)->cambiarEstado($propietario, $pedido, ['version' => $pedido->version, 'estado' => 'en_fabricacion', 'clave_operacion' => fake()->uuid()]);
                    }
                }
            }
        }
    }
}
