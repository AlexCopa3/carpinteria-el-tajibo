<?php

namespace Database\Seeders;

use App\Models\TipoMueble;
use Illuminate\Database\Seeder;

class TiposMuebleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['escritorio' => 'Escritorio', 'mesa' => 'Mesa', 'silla' => 'Silla', 'armario' => 'Armario', 'estante' => 'Estante', 'puerta' => 'Puerta', 'velador' => 'Velador', 'comoda' => 'Cómoda', 'vitrina' => 'Vitrina'] as $codigo => $nombre) {
            TipoMueble::firstOrCreate(['codigo' => $codigo], ['nombre' => $nombre, 'activo' => true]);
        }
    }
}
