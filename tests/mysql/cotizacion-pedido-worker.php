<?php

use App\Models\Pedido;
use App\Models\User;
use App\Services\PedidoService;
use App\Services\PagoService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$base = config('database.connections.mysql.database');
if (! app()->environment('testing') || ! preg_match('/^carpinteria_el_tajibo_ensayo_directa_[0-9]{8}_[0-9]{6}$/D', $base) || DB::selectOne('SELECT DATABASE() nombre')->nombre !== $base) { throw new RuntimeException('Worker fuera de la copia autorizada.'); }
$archivo = realpath($argv[1] ?? '');
$raiz = realpath(base_path('storage/app/private/edicion-directa-2026-11-16'));
if (! $archivo || ! str_starts_with($archivo, $raiz.DIRECTORY_SEPARATOR)) { throw new RuntimeException('Entrada fuera del ensayo.'); }
$entrada = json_decode(file_get_contents($archivo), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($archivo.'.ready', '1');
$limite = microtime(true) + 20;
while (! is_file($entrada['continuar'])) { if (microtime(true) > $limite) throw new RuntimeException('Sin señal de inicio.'); usleep(10000); }
try {
    $actor = User::findOrFail($entrada['actor']);
    if ($entrada['accion'] === 'pago') { $resultado = app(PagoService::class)->guardar($actor, $entrada['datos']); }
    else { $resultado = app(PedidoService::class)->aceptarCotizacion($actor, Pedido::findOrFail($entrada['pedido']), $entrada['datos']); }
    echo json_encode(['resultado' => 'ok', 'id' => $resultado->id]);
} catch (ValidationException $error) { echo json_encode(['resultado' => 'validacion', 'campos' => array_keys($error->errors())]); }
