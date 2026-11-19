<?php

use App\Models\CasoHistorico;
use App\Models\Categoria;
use App\Models\Material;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\CatalogoService;
use App\Services\CotizacionService;
use Database\Seeders\CasosHistoricosSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CasosPerfil;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$dir = realpath(getenv('TAJIBO_QA_DIR') ?: '');
$raiz = realpath(storage_path('framework/testing'));
$db = realpath(config('database.connections.sqlite.database'));
if (! $dir || ! $raiz || ! str_starts_with($dir, $raiz.DIRECTORY_SEPARATOR) || ! app()->environment('testing') || config('database.default') !== 'sqlite' || $db !== $dir.DIRECTORY_SEPARATOR.'browser.sqlite') {
    throw new RuntimeException('Fixture fuera de SQLite temporal.');
}
$actor = User::where('email', 'propietario@example.test')->firstOrFail();
$entrada = json_decode(file_get_contents(database_path('seeders/data/casos_historicos.json')), true, 512, JSON_THROW_ON_ERROR);
foreach ($entrada['casos'] as $caso) {
    foreach ($caso['materiales'] as $material) {
        if (Material::where('nombre', $material['material'])->exists()) {
            continue;
        }
        $principal = in_array($material['material'], ['Melamina', 'Madera'], true);
        $categoria = Categoria::firstOrCreate(['nombre' => $principal ? 'Material principal' : 'Accesorios de casos QA'], ['activo' => true]);
        app(CatalogoService::class)->guardar($actor, ['nombre' => $material['material'], 'categoria_id' => $categoria->id, 'unidad_id' => UnidadMaterial::where('codigo', $material['unidad'])->value('id'), 'precio_referencia' => $material['costo_unitario_estimado_bs'], 'activo' => true]);
    }
}
config(['datos_sistema.habilitado' => true]);
CasosPerfil::cargar();
$madera = Material::where('nombre', 'Madera')->firstOrFail();
$detalle = ['tipo_mueble_id' => TipoMueble::where('codigo', 'mesa')->value('id'), 'material_principal_id' => $madera->id, 'largo_cm' => '150', 'ancho_cm' => '85', 'alto_cm' => '76', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural', 'cantidad_muebles' => 2, 'mano_obra_unitaria' => '20', 'otros_costos_total' => '5', 'materiales' => [['material_id' => $madera->id, 'componente' => 'General', 'cantidad_unitaria' => '2', 'costo_unitario_estimado' => '75', 'estado_costo' => 'conocido', 'fuente_costo' => 'estimacion_manual']]];
$otro = [...$detalle, 'largo_cm' => '300'];
$cotizacion = app(CotizacionService::class)->guardar($actor, ['fecha' => today()->toDateString(), 'clave_registro' => (string) Str::uuid(), 'detalles' => [$detalle, $otro]]);
file_put_contents($dir.'/casos5a.json', json_encode(['primero' => CasoHistorico::where('lote', CasosHistoricosSeeder::LOTE)->orderBy('posicion_lote')->value('id'), 'simulados' => CasoHistorico::where('lote', CasosHistoricosSeeder::LOTE)->count(), 'materiales' => DB::table('materiales_caso')->whereIn('caso_historico_id', CasoHistorico::where('lote', CasosHistoricosSeeder::LOTE)->pluck('id'))->count(), 'cotizacion_similitud' => $cotizacion->id, 'detalle_similitud' => $cotizacion->detalles[0]->id, 'detalle_fuera_cobertura' => $cotizacion->detalles[1]->id]));
