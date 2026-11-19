<?php

use App\Models\Pedido;
use App\Models\User;
use App\Services\PedidoService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^carpinteria_el_tajibo_ensayo4b_[0-9]{14}_[a-f0-9]{6}$/D', $db)) {
    throw new RuntimeException('Base de prueba inválida.');
}
config(['database.connections.prueba4b' => array_replace(config('database.connections.mysql'), ['database' => $db]), 'database.default' => 'prueba4b', 'cache.default' => 'array', 'permission.cache.store' => 'array']);
if (DB::selectOne('SELECT DATABASE() nombre')->nombre !== $db) {
    throw new RuntimeException('Destino inválido.');
}
$entrada = json_decode(file_get_contents($argv[2]), true, 512, JSON_THROW_ON_ERROR);
$actor = User::findOrFail($entrada['actor']);
try {
    $pedido = ($entrada['operacion'] === 'crear') ? app(PedidoService::class)->crear($actor, $entrada['datos']) : app(PedidoService::class)->cambiarEstado($actor, Pedido::findOrFail($entrada['pedido']), $entrada['datos']);
    echo json_encode(['resultado' => 'ok', 'pedido' => $pedido->id]);
} catch (ValidationException $error) {
    echo json_encode(['resultado' => 'validacion', 'campos' => array_keys($error->errors())]);
}
