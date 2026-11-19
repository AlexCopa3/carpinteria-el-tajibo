<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $propietario = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            foreach ([
                ['name' => 'Luis Fernando Quispe', 'email' => 'luis.quispe@example.test', 'numero_identificacion' => '8001001', 'telefono' => '+591 72010001', 'direccion' => 'El Alto · Villa Adela'],
                ['name' => 'Miguel Ángel Mamani', 'email' => 'miguel.mamani@example.test', 'numero_identificacion' => '8001002', 'telefono' => '+591 72010002', 'direccion' => 'La Paz · San Pedro'],
            ] as $datos) {
                if (! User::where('email', $datos['email'])->exists()) {
                    app(UsuarioService::class)->crear($propietario, $datos + ['tipo_identificacion' => 'ci', 'rol' => 'maestro']);
                }
            }
        });
    }
}
