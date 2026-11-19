<?php

namespace Database\Seeders;

use App\Models\Ajuste;
use App\Services\AuditoriaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AjustesSeeder extends Seeder
{
    public function run(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            if (Ajuste::query()->lockForUpdate()->exists()) {
                return;
            }
            $ajuste = new Ajuste;
            $ajuste->nombre = 'Carpintería El Tajibo';
            $ajuste->descripcion = 'Fabricación de muebles a medida.';
            $ajuste->direccion = 'El Alto · La Paz, Bolivia';
            $ajuste->telefono = '+591 70000000';
            $ajuste->email = 'contacto.tajibo@example.test';
            $ajuste->save();
            app(AuditoriaService::class)->registrar(null, $ajuste, 'creado', [], app(AuditoriaService::class)->datos($ajuste) + ['fuente' => 'AjustesSeeder']);
        });
    }
}
