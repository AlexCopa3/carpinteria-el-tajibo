<?php

// Exclusivamente para la copia restaurada de ensayo; no ejecuta operaciones en la base local.
use App\Models\{Categoria, Cliente, TipoMueble, UnidadMaterial, User};
use App\Services\{CatalogoService, CotizacionService, PedidoService, FabricacionService};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function prepararCotizacionPedido(): array
{
    $base = DB::selectOne('SELECT DATABASE() nombre')->nombre;
    if (! preg_match('/^carpinteria_el_tajibo_ensayo_directa_[0-9]{8}_[0-9]{6}$/D', $base)) { throw new RuntimeException('Fixture fuera del ensayo.'); }
    $actor = User::factory()->create()->assignRole('propietario');
    $categoria = Categoria::firstOrCreate(['nombre' => 'Material principal'], ['activo' => true]);
    $material = app(CatalogoService::class)->guardar($actor, ['nombre' => 'Material ensayo '.Str::uuid(), 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', 'metro')->value('id'), 'precio_referencia' => '40', 'activo' => true]);
    $detalle = ['tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'), 'material_principal_id' => $material->id, 'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural', 'cantidad_muebles' => 2, 'mano_obra_unitaria' => '20', 'otros_costos_total' => '5', 'materiales' => [['material_id' => $material->id, 'componente' => 'General', 'cantidad_unitaria' => '3', 'costo_unitario_estimado' => '40', 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo']]];
    $servicio = app(CotizacionService::class);
    $cotizacion = $servicio->guardar($actor, ['clave_registro' => (string) Str::uuid(), 'fecha' => today()->toDateString(), 'detalles' => [$detalle]]);
    $cotizacion = $servicio->aprobar($actor, $cotizacion, ['version' => $cotizacion->version, 'precios' => [$cotizacion->detalles->first()->id => '300']]);
    $cliente = Cliente::create(['nombre' => 'Cliente aislado de ensayo', 'tipo' => 'persona', 'telefono' => '70000000']);
    $pedido = app(PedidoService::class)->crear($actor, ['cotizacion_id' => $cotizacion->id, 'cliente_id' => $cliente->id, 'aceptacion' => true, 'clave_registro' => (string) Str::uuid()]);
    $pedido = app(PedidoService::class)->cambiarEstado($actor, $pedido, ['version' => $pedido->version, 'estado' => 'en_fabricacion', 'clave_operacion' => (string) Str::uuid()]);
    $fila = $pedido->detalles->first()->materiales->first();
    $pedido = app(FabricacionService::class)->guardar($actor, $pedido, ['version' => $pedido->version, 'materiales' => [['id' => $fila->id, 'detalle_pedido_id' => $fila->detalle_pedido_id, 'material_id' => $fila->material_id, 'cantidad_utilizada' => '6']]]);
    return ['actor' => $actor->id, 'pedido' => $pedido->id, 'detalle' => $fila->detalle_pedido_id, 'fila' => $fila->id, 'version' => $pedido->version];
}
