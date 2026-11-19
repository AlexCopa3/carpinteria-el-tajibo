<?php

use App\Models\Pedido;
use App\Models\User;
use App\Services\FabricacionService;
use App\Services\CierrePedidoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$base = config('database.connections.mysql.database');
if (! app()->environment('testing') || ! preg_match('/^carpinteria_el_tajibo_ensayo_4d_[0-9]{14}_[a-f0-9]{6}$/D', $base) || DB::selectOne('SELECT DATABASE() nombre')->nombre !== $base) {
    throw new RuntimeException('Worker fuera de la copia MySQL autorizada.');
}
$archivo = realpath($argv[1] ?? '');
$directorio = realpath(base_path('storage/app/private/fase4d-2026-11-09'));
if (! $archivo || ! str_starts_with($archivo, $directorio.DIRECTORY_SEPARATOR)) {
    throw new RuntimeException('Entrada fuera del directorio de ensayo.');
}
$entrada = json_decode(file_get_contents($archivo), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($archivo.'.ready', '1');
$inicio = microtime(true);
while (! is_file($directorio.'/continuar-'.($entrada['ronda'] ?? preg_replace('/^worker-(.*)-[01]\.json$/', '$1', basename($archivo))))) {
    if (microtime(true) - $inicio > 20) {
        throw new RuntimeException('Tiempo de espera agotado.');
    }
    usleep(10000);
}
try {
    $actor = User::findOrFail($entrada['actor']);
    $pedido = Pedido::findOrFail($entrada['pedido']);
    $pedido = match ($entrada['accion']) {
        'uso' => app(FabricacionService::class)->guardar($actor, $pedido, $entrada['datos']),
        'validar' => app(CierrePedidoService::class)->validarMateriales($actor, $pedido, $entrada['datos']),
        'terminar' => app(CierrePedidoService::class)->terminar($actor, $pedido, $entrada['datos']),
        'entregar' => app(CierrePedidoService::class)->entregar($actor, $pedido, $entrada['datos']),
    };
    echo json_encode(['resultado' => 'ok', 'version' => $pedido->version]);
} catch (ValidationException $error) {
    echo json_encode(['resultado' => 'validacion', 'campos' => array_keys($error->errors())]);
} catch (AuthorizationException $error) {
    echo json_encode(['resultado' => 'prohibido']);
}
