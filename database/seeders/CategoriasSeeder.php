<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Services\AuditoriaService;
use Illuminate\Database\Seeder;

class CategoriasSeeder extends Seeder
{
    public function run(array &$carga = []): void
    {
        if (! isset($carga['lote'], $carga['propietario'])) {
            throw new \RuntimeException('Ejecuta DatosSistemaSeeder para respetar el orden y el control de duplicados.');
        }
        $propietario = $carga['propietario'];
        $categorias = [];
        foreach (['Material principal', 'Herrajes', 'Acabados', 'Adhesivos', 'Accesorios'] as $nombre) {
            $categoria = Categoria::where('nombre', $nombre)->first();
            if (! $categoria) {
                $categoria = Categoria::factory()->create(['nombre' => $nombre]);
            }
            if (! $categoria->activo) {
                throw new \RuntimeException('La categoría '.$nombre.' está inactiva. No se cambiará automáticamente.');
            }
            $categorias[$nombre] = $categoria;
            if ($categoria->wasRecentlyCreated) {
                app(AuditoriaService::class)->registrar(null, $categoria, 'creado', [], ['nombre' => $nombre]);
            }
        }
        $carga['categorias'] = $categorias;

    }
}
