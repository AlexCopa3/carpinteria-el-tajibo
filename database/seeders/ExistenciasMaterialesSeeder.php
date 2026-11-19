<?php

namespace Database\Seeders;

use App\Services\ExistenciaMaterialService;
use Illuminate\Database\Seeder;

class ExistenciasMaterialesSeeder extends Seeder
{
    public function run(array &$carga = []): void
    {
        if (! isset($carga['lote'], $carga['propietario'])) {
            throw new \RuntimeException('Ejecuta DatosSistemaSeeder para respetar el orden y el control de duplicados.');
        }
        $propietario = $carga['propietario'];
        foreach ($carga['existencias'] as $entrada) {
            $material = $entrada['material'];
            if ($entrada['cantidad'] !== '0') {
                app(ExistenciaMaterialService::class)->guardarCantidad($propietario, $material, [
                    'clave' => fake()->uuid(), 'cantidad' => $entrada['cantidad'],
                    'unidad_id' => $material->unidad_id, 'existencia_esperada' => '0',
                    'motivo' => 'Cantidades disponibles en el taller.',
                ]);
            }
        }
    }
}
