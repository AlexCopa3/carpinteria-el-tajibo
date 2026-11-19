<?php

use App\Models\CasoHistorico;
use App\Models\TipoMueble;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$dir = realpath(getenv('TAJIBO_QA_DIR') ?: '');
$raiz = realpath(storage_path('framework/testing'));
if (! $dir || ! $raiz || ! str_starts_with($dir, $raiz.DIRECTORY_SEPARATOR) || ! app()->environment('testing') || config('database.default') !== 'mysql' || ! preg_match('/^carpinteria_el_tajibo_ensayo_datos_[0-9]{14}_[a-f0-9]{6}$/D', DB::connection()->getDatabaseName())) {
    throw new RuntimeException('Destino QA no autorizado');
}
if (DB::table('casos_historicos')->count() !== 87) {
    throw new RuntimeException('Faltan casos');
}
$password = bin2hex(random_bytes(20));
$user = User::role('propietario')->sole();
$user->name = 'Propietario de prueba';
$user->email = 'propietario@example.test';
$user->password = Hash::make($password);
$user->password_temporal = false;
$user->save();
$grupos = DB::table('casos_historicos as c')->join('detalles_pedido as d', 'd.id', '=', 'c.detalle_pedido_id')->selectRaw('d.pedido_id,COUNT(*) cantidad,MIN(c.id) caso_id')->groupBy('d.pedido_id')->get();
$datos = [];
foreach ([1, 2, 3, 4] as $n) {
    $datos[] = (array) $grupos->firstWhere('cantidad', $n);
}
file_put_contents($dir.'/credenciales.json', json_encode(['password' => $password, 'pedido_antiguo' => DB::table('pedidos')->min('id')]));
file_put_contents($dir.'/datos-historial.json', json_encode($datos));

$consultas = [];
foreach (['velador', 'comoda', 'vitrina'] as $codigo) {
    $tipo = TipoMueble::where('codigo', $codigo)->sole();
    $caso = CasoHistorico::where('tipo_mueble_id', $tipo->id)->orderBy('posicion_lote')->firstOrFail();
    $consultas[] = ['tipo' => $tipo->nombre, 'mueble' => $caso->only(['tipo_mueble_id', 'material_principal_id', 'largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones', 'cantidad_muebles', 'acabado'])];
}
if (getenv('TAJIBO_REVISAR_CONSUMOS')) {
    $caso = CasoHistorico::findOrFail(86);
    $consultas[] = ['tipo' => 'Mesa', 'mueble' => $caso->only(['tipo_mueble_id', 'material_principal_id', 'largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones', 'cantidad_muebles', 'acabado'])];
}
file_put_contents($dir.'/consultas-historial.json', json_encode($consultas));
$perfil = config_path(config('similitud.perfil.archivo'));
config(['similitud.perfil' => ['archivo' => $perfil, 'sha256' => hash_file('sha256', $perfil)]]);
file_put_contents($dir.'/config.php', '<?php return '.var_export(config()->all(), true).';');
