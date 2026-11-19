<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Material;
use App\Models\UnidadMaterial;
use App\Services\CatalogoService;
use Brick\Math\BigDecimal;
use Illuminate\Database\Seeder;

class MaterialesSeeder extends Seeder
{
    public function run(array &$carga = []): void
    {
        if (! isset($carga['lote'], $carga['propietario'])) {
            throw new \RuntimeException('Ejecuta DatosSistemaSeeder para respetar el orden y el control de duplicados.');
        }
        $propietario = $carga['propietario'];
        $categorias = $carga['categorias'];
        $existencias = [];
        if ($carga['lote'] === DatosSistemaSeeder::PRIMERA_CARGA) {
            $catalogo = [
                ['Melamina', 'Material principal', 'tablero', '280', '3'],
                ['Madera', 'Material principal', 'pieza', '75', '8'],
                ['Bisagra', 'Herrajes', 'unidad', '7.50', '80'],
                ['Juego de correderas', 'Herrajes', 'unidad', '35', '16'],
                ['Tornillo', 'Herrajes', 'unidad', '0.20', '500'],
                ['Barniz', 'Acabados', 'litro', '45', '10'],
                ['Pintura', 'Acabados', 'litro', '38', '8'],
                ['Cola para madera', 'Adhesivos', 'litro', '30', '6'],
                ['Lija', 'Accesorios', 'unidad', '5', '30'],
                ['Canto PVC', 'Accesorios', 'metro', '3.50', '25'],
                ['Adhesivo con referencia pendiente', 'Adhesivos', 'litro', '0', '0'],
            ];
            $materiales = [];
            foreach ($catalogo as [$nombre,$categoria,$unidad,$precio,$cantidad]) {
                $nombreRegistro = $nombre;
                $sufijo = 0;
                while (Material::where('nombre', $nombreRegistro)->exists()) {
                    $sufijo++;
                    $nombreRegistro = $nombre.' estándar'.($sufijo > 1 ? ' '.$sufijo : '');
                }
                $atributos = Material::factory()->make(['nombre' => $nombreRegistro, 'categoria_id' => $categorias[$categoria]->id, 'unidad_id' => UnidadMaterial::where('codigo', $unidad)->value('id'), 'precio_referencia' => $precio])->only(['nombre', 'descripcion', 'especificaciones', 'categoria_id', 'unidad_id', 'precio_referencia', 'activo']);
                $material = app(CatalogoService::class)->guardar($propietario, $atributos);
                $materiales[$nombre] = $material;
                $existencias[] = ['material' => $material, 'cantidad' => $cantidad];
            }

            $bases = array_intersect_key($materiales, array_flip(['Melamina', 'Madera']));
        } else {
            $primera = Auditoria::where('accion', 'datos_ejemplo_cargados')->where('datos_nuevos->lote', 'datos_bolivianos_20261105_v1')->first();
            if (! $primera) {
                throw new \RuntimeException('Falta la primera carga de DatosSistemaSeeder.');
            }
            $principales = Material::with('unidad')->principales()->whereIn('id', $primera->datos_nuevos['ids']['materiales'])->get();
            $bases = [];
            foreach (['Melamina', 'Madera'] as $nombre) {
                $base = $principales->first(fn ($material) => str_starts_with($material->nombre, $nombre));
                if (! $base || ! $base->precio_referencia || BigDecimal::of($base->precio_referencia)->isLessThanOrEqualTo(0)) {
                    throw new \RuntimeException('Revisa el material principal activo y su costo: '.$nombre.'. No se alterará automáticamente.');
                }
                $bases[$nombre] = $base;
            }

            $catalogo = [
                ['Tornillo para madera', 'Herrajes', 'unidad', '0.35', '400'],
                ['Tornillo confirmat', 'Herrajes', 'unidad', '0.60', '250'],
                ['Bisagra de cazoleta', 'Herrajes', 'unidad', '9.50', '60'],
                ['Pomo de madera', 'Herrajes', 'unidad', '12', '25'],
                ['Tirador metálico', 'Herrajes', 'unidad', '15', '35'],
                ['Cerradura para mueble', 'Herrajes', 'unidad', '28', '20'],
                ['Pata regulable', 'Herrajes', 'unidad', '18', '20'],
                ['Soporte de repisa', 'Herrajes', 'unidad', '3', '120'],
                ['Barniz mate', 'Acabados', 'litro', '50', '7'],
                ['Sellador para madera', 'Acabados', 'litro', '42', '5'],
                ['Disolvente', 'Acabados', 'litro', '22', '6'],
                ['Masilla para madera', 'Acabados', 'kilogramo', '16', '4'],
                ['Cola blanca', 'Adhesivos', 'litro', '32', '10'],
                ['Lija grano 120', 'Accesorios', 'unidad', '4', '40'],
                ['Perfil de aluminio', 'Accesorios', 'metro', '25', '12'],
            ];
            $materiales = [];
            foreach ($catalogo as [$nombre,$categoria,$unidad,$precio,$cantidad]) {
                $grupo = Categoria::where('nombre', $categoria)->where('activo', true)->firstOrFail();
                $nombreRegistro = $nombre;
                for ($sufijo = 1; Material::where('nombre', $nombreRegistro)->exists(); $sufijo++) {
                    $nombreRegistro = $nombre.' estándar'.($sufijo > 1 ? ' '.$sufijo : '');
                }
                $atributos = Material::factory()->make(['nombre' => $nombreRegistro, 'categoria_id' => $grupo->id, 'unidad_id' => UnidadMaterial::where('codigo', $unidad)->firstOrFail()->id, 'precio_referencia' => $precio])->only(['nombre', 'descripcion', 'especificaciones', 'categoria_id', 'unidad_id', 'precio_referencia', 'activo']);
                $material = app(CatalogoService::class)->guardar($propietario, $atributos);
                $materiales[$nombre] = $material;
                $existencias[] = ['material' => $material, 'cantidad' => $cantidad];
            }

        }
        $carga['materiales'] = $materiales;
        $carga['bases'] = $bases;
        $carga['existencias'] = $existencias;
    }
}
