<?php

use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\TiposMuebleSeeder;
use Database\Seeders\UnidadesSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$dir = realpath(getenv('TAJIBO_QA_DIR') ?: '');
$base = realpath(storage_path('framework/testing'));
$db = realpath(config('database.connections.sqlite.database'));
if (! $dir || ! $base || ! str_starts_with($dir, $base.DIRECTORY_SEPARATOR)
    || config('database.default') !== 'sqlite' || ! app()->environment('testing')
    || $db !== $dir.DIRECTORY_SEPARATOR.'browser.sqlite') {
    throw new RuntimeException('Las fixtures de navegador solo se crean en la base SQLite aislada.');
}
if (filesize($db) !== 0) {
    throw new RuntimeException('La base de pruebas debe ser nueva y vacía.');
}
// El destino ya se verificó como SQLite nueva y aislada. Aplicar el esquema completo.
if ($kernel->call('migrate', ['--no-interaction' => true]) !== 0) {
    throw new RuntimeException('No se pudo preparar el esquema completo de navegador.');
}
$kernel->call('db:seed', ['--class' => PermisosSeeder::class, '--no-interaction' => true]);
$kernel->call('db:seed', ['--class' => UnidadesSeeder::class, '--no-interaction' => true]);
$kernel->call('db:seed', ['--class' => TiposMuebleSeeder::class, '--no-interaction' => true]);
$password = Str::password(24);
foreach (['propietario' => 'Propietario de prueba', 'maestro' => 'Maestro de prueba'] as $rol => $nombre) {
    User::create(['name' => $nombre, 'email' => $rol.'@example.test', 'password' => $password])->assignRole($rol);
}
file_put_contents($dir.'/credenciales.json', json_encode(['password' => $password]));
echo 'Fixtures creadas solo en SQLite de pruebas.'.PHP_EOL;
