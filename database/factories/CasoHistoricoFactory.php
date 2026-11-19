<?php

namespace Database\Factories;

use App\Models\CasoHistorico;
use App\Models\Material;
use App\Models\MaterialCaso;
use App\Models\TipoMueble;
use Illuminate\Database\Eloquent\Factories\Factory;

class CasoHistoricoFactory extends Factory
{
    protected $model = CasoHistorico::class;

    public function definition(): array
    {
        return ['tipo_mueble_id' => fn () => TipoMueble::firstOrCreate(['codigo' => 'mesa'], ['nombre' => 'Mesa', 'activo' => true])->id, 'tipo_nombre' => 'Mesa', 'material_principal_id' => Material::factory(), 'material_principal_nombre' => 'Material de prueba', 'fecha_referencia' => '2026-03-01', 'largo_cm' => '120', 'ancho_cm' => '60', 'alto_cm' => '75', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural', 'cantidad_muebles' => 1, 'origen' => 'simulado', 'fuente' => 'registro_inicial', 'fuente_caracteristicas' => 'escenario_sintetico', 'fuente_costos' => 'estimacion_inicial', 'estado_revision' => 'pendiente', 'costo_materiales_estimado_total' => '50', 'mano_obra_estimada_total' => '20', 'otros_costos_estimados_total' => '0', 'costo_estimado_total' => '70', 'precio_historico_total' => '100', 'version' => 1];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (CasoHistorico $caso) {
            $material = $caso->materialPrincipal;
            $linea = new MaterialCaso;
            $linea->forceFill(['caso_historico_id' => $caso->id, 'posicion' => 1, 'material_id' => $material->id, 'unidad_id' => $material->unidad_id, 'material_nombre' => $material->nombre, 'unidad_nombre' => $material->unidad->nombre, 'cantidad_total' => '1', 'costo_unitario_estimado' => '50', 'subtotal_estimado' => '50', 'fuente_costo' => 'estimacion_inicial'])->save();
        });
    }
}
