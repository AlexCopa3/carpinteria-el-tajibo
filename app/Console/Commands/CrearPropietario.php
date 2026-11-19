<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditoriaService;
use App\Support\ReglasContrasena;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CrearPropietario extends Command
{
    protected $signature = 'tajibo:crear-propietario';

    protected $description = 'Crea de forma interactiva la primera cuenta del propietario, sin credenciales fijas';

    public function handle(): int
    {
        $aislada = app()->environment('testing') && config('database.default') === 'sqlite'
            && config('database.connections.sqlite.database') === ':memory:';
        if (! $aislada) {
            if (config('database.default') !== 'mysql'
                || config('database.connections.mysql.database') !== 'carpinteria_el_tajibo') {
                $this->error('Configura primero la base MySQL exclusiva carpinteria_el_tajibo.');

                return self::FAILURE;
            }
            if (DB::selectOne('SELECT DATABASE() AS nombre')->nombre !== 'carpinteria_el_tajibo') {
                $this->error('La base activa no es la autorizada.');

                return self::FAILURE;
            }
        }

        $lock = Cache::lock('tajibo.crear-primer-propietario', 300);
        if (! $lock->get()) {
            $this->error('Ya hay un proceso de creación en curso.');

            return self::FAILURE;
        }

        try {
            if (User::whereHas('roles', fn ($query) => $query->where('name', 'propietario')->where('guard_name', 'web'))->exists()) {
                $this->error('Ya existe un propietario. Usa la interfaz protegida para administrar cuentas.');

                return self::FAILURE;
            }
            if (! $this->input->isInteractive()) {
                $this->error('Ejecuta sin --no-interaction; la contraseña se solicita de forma oculta.');

                return self::FAILURE;
            }
            if (! Role::where('name', 'propietario')->where('guard_name', 'web')->exists()) {
                $this->error('Ejecuta primero el seeder de permisos.');

                return self::FAILURE;
            }
            $datos = [
                'name' => $this->ask('Nombre del propietario'),
                'email' => mb_strtolower(trim((string) $this->ask('Correo electrónico'))),
                'password' => $this->secret('Contraseña (12 caracteres, mayúscula, minúscula, número y símbolo)'),
                'password_confirmation' => $this->secret('Repite la contraseña'),
            ];
            $validator = Validator::make($datos, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ReglasContrasena::reglas(),
            ]);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }
            $creado = DB::transaction(function () use ($datos): bool {
                $rol = Role::where('name', 'propietario')->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
                if (User::whereHas('roles', fn ($query) => $query->where('roles.id', $rol->id))->exists()) {
                    return false;
                }
                $usuario = User::create(['name' => $datos['name'], 'email' => $datos['email'], 'password' => $datos['password']]);
                $usuario->assignRole($rol);
                $auditoria = app(AuditoriaService::class);
                $auditoria->registrar(null, $usuario, 'creado', [], $auditoria->datos($usuario) + ['rol' => 'propietario', 'origen' => 'Configuración inicial por consola']);

                return true;
            });
            unset($datos);
            if (! $creado) {
                $this->error('Ya se creó un propietario en otro proceso. No se creó otra cuenta.');

                return self::FAILURE;
            }
            $this->info('Propietario creado. Ya puedes iniciar sesión con las credenciales que elegiste.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
