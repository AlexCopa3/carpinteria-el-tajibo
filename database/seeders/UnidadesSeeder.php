<?php

namespace Database\Seeders;

use App\Models\UnidadMaterial;
use Illuminate\Database\Seeder;

class UnidadesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['unidad', 'Unidad', false], ['pieza', 'Pieza', false], ['tablero', 'Tablero', false], ['metro', 'Metro', true], ['litro', 'Litro', true], ['kilogramo', 'Kilogramo', true]] as [$codigo,$nombre,$fraccionaria]) {
            UnidadMaterial::firstOrCreate(['codigo' => $codigo], compact('nombre', 'fraccionaria'));
        }
    }
}
