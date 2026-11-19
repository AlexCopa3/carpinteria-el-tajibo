<?php

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\CatalogoService;

// La preparación base rechaza cualquier destino distinto de SQLite temporal nueva.
require __DIR__.'/preparar.php';

$propietario = User::where('email', 'propietario@example.test')->firstOrFail();
$cliente = Cliente::create(['nombre' => 'Cliente de prueba 4A', 'tipo' => 'persona', 'telefono' => '70000000', 'activo' => true]);
$categoria = Categoria::create(['nombre' => 'Material principal']);
$unidad = UnidadMaterial::where('codigo', 'metro')->firstOrFail();
$catalogo = app(CatalogoService::class);
$material = $catalogo->guardar($propietario, ['nombre' => 'Material de prueba 4A', 'categoria_id' => $categoria->id, 'unidad_id' => $unidad->id, 'precio_referencia' => '40', 'activo' => true]);
$pendiente = $catalogo->guardar($propietario, ['nombre' => 'Material con referencia pendiente', 'categoria_id' => $categoria->id, 'unidad_id' => $unidad->id, 'precio_referencia' => '0', 'activo' => true]);
$accesorios = Categoria::create(['nombre' => 'Accesorios de prueba']);
$secundario = $catalogo->guardar($propietario, ['nombre' => 'Accesorio de prueba', 'categoria_id' => $accesorios->id, 'unidad_id' => $unidad->id, 'precio_referencia' => '5', 'activo' => true]);
file_put_contents($dir.'/datos4a.json', json_encode([
    'secundario' => $secundario->id, 'cliente' => $cliente->id, 'material' => $material->id, 'pendiente' => $pendiente->id,
    'mesa' => TipoMueble::where('codigo', 'mesa')->value('id'),
    'silla' => TipoMueble::where('codigo', 'silla')->value('id'),
]));
