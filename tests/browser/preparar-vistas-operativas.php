<?php

require __DIR__.'/preparar-cotizaciones.php';

$actor = App\Models\User::where('email', 'propietario@example.test')->firstOrFail();
$detalle = [
    'tipo_mueble_id' => App\Models\TipoMueble::where('codigo', 'mesa')->value('id'),
    'material_principal_id' => $material->id, 'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75',
    'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural',
    'cantidad_muebles' => 2, 'mano_obra_unitaria' => '30', 'otros_costos_total' => '10',
    'materiales' => [['material_id' => $material->id, 'componente' => 'General', 'cantidad_unitaria' => '2',
        'costo_unitario_estimado' => '40', 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo']],
];
$otro = $detalle;
$otro['tipo_mueble_id'] = App\Models\TipoMueble::where('codigo', 'silla')->value('id');
$otro['cantidad_muebles'] = 1;
$service = app(App\Services\CotizacionService::class);
$cotizacion = $service->guardar($actor, ['clave_registro' => (string) Illuminate\Support\Str::uuid(), 'fecha' => today()->toDateString(), 'detalles' => [$detalle, $otro]]);
$cotizacion = $service->aprobar($actor, $cotizacion, ['version' => $cotizacion->version, 'precios' => $cotizacion->detalles->mapWithKeys(fn ($detalle) => [$detalle->id => '300'])->all()]);
$pedido = app(App\Services\PedidoService::class)->crear($actor, ['cotizacion_id' => $cotizacion->id, 'clave_registro' => (string) Illuminate\Support\Str::uuid(), 'aceptacion' => true, 'cliente_id' => $cliente->id]);
$pago = app(App\Services\PagoService::class)->guardar($actor, ['pedido_id' => $pedido->id, 'clave_registro' => (string) Illuminate\Support\Str::uuid(), 'fecha' => today()->toDateString(), 'importe' => '75.50', 'medio' => 'efectivo', 'referencia' => 'Recibo de prueba', 'observaciones' => 'Registro aislado para revisión visual']);
file_put_contents($dir.'/vistas-operativas.json', json_encode(['pedido' => $pedido->id, 'pago' => $pago->id, 'cliente' => $cliente->id], JSON_THROW_ON_ERROR));
