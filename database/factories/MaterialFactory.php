<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Material;
use App\Models\UnidadMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        // Ejecutar UnidadesSeeder antes de usar esta factory.
        return [
            'nombre' => ''.fake()->unique()->numerify('Material ###'),
            'categoria_id' => Categoria::factory(),
            'categoria' => 'Categoría de prueba',
            'unidad_id' => UnidadMaterial::where('codigo', 'unidad')->firstOrFail()->id,
            'descripcion' => 'Material para fabricación de muebles.',
            'especificaciones' => 'Cantidades y costos sujetos a revisión del taller.',
            'precio_referencia' => (string) fake()->numberBetween(5, 100),
            'activo' => true,
        ];
    }
}
