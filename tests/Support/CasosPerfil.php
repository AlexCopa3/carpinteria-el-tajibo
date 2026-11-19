<?php

namespace Tests\Support;

use App\Models\CasoHistorico;
use App\Models\Categoria;
use App\Models\Material;
use App\Models\MaterialCaso;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Seeders\CasosHistoricosSeeder;

// Corpus fijo de pruebas del comparador; no forma parte de la instalación de datos.
class CasosPerfil
{
    public static function cargar(bool $ampliado = true): void
    {
        $documento = json_decode(file_get_contents(database_path('seeders/data/casos_historicos.json')), true, 512, JSON_THROW_ON_ERROR);
        if ($ampliado) {
            $adicionales = json_decode(file_get_contents(database_path('seeders/data/casos_historicos_ampliacion.json')), true, 512, JSON_THROW_ON_ERROR);
            $documento['casos'] = array_merge($documento['casos'], array_values(array_filter($adicionales['casos'], fn ($c) => $c['referencia_perfil'])));
            $perfil = config_path(config('similitud.perfil.archivo'));
            config(['similitud.perfil' => ['archivo' => $perfil, 'sha256' => hash_file('sha256', $perfil)]]);
            $categoria = Categoria::firstOrCreate(['nombre' => 'Accesorios']);
            Material::firstOrCreate(['nombre' => 'Vidrio para vitrina'], ['categoria_id' => $categoria->id, 'categoria' => $categoria->nombre, 'unidad_id' => UnidadMaterial::where('codigo', 'pieza')->sole()->id, 'precio_referencia' => '90.00', 'activo' => true]);
        }
        foreach ($documento['casos'] as $entrada) {
            $principal = Material::where('nombre', $entrada['material_principal'])->firstOrFail();
            $tipo = TipoMueble::where('codigo', $entrada['tipo_mueble'])->firstOrFail();
            $caso = new CasoHistorico;
            $caso->forceFill([
                'tipo_mueble_id' => $tipo->id, 'tipo_nombre' => $tipo->nombre, 'material_principal_id' => $principal->id, 'material_principal_nombre' => $principal->nombre,
                'fecha_referencia' => $entrada['fecha_referencia'], 'acabado' => $entrada['acabado'], 'cantidad_muebles' => $entrada['cantidad_muebles'],
                'descripcion' => $entrada['descripcion'], 'contexto' => $entrada['contexto'], 'origen' => 'simulado', 'fuente' => 'registro_inicial',
                'fuente_caracteristicas' => 'escenario_sintetico', 'fuente_costos' => 'estimacion_inicial', 'estado_revision' => 'pendiente',
                'costo_materiales_estimado_total' => $entrada['costo_materiales_estimado_total_bs'], 'mano_obra_estimada_total' => $entrada['mano_obra_estimada_total_bs'],
                'otros_costos_estimados_total' => $entrada['otros_costos_estimados_total_bs'], 'costo_estimado_total' => $entrada['costo_estimado_total_bs'],
                'precio_historico_total' => $entrada['precio_historico_ilustrativo_total_bs'], 'lote' => CasosHistoricosSeeder::LOTE, 'posicion_lote' => $entrada['indice_lote'], 'version' => 1,
            ] + array_intersect_key($entrada, array_flip(['largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones'])))->save();
            foreach ($entrada['materiales'] as $indice => $fila) {
                $material = Material::where('nombre', $fila['material'])->firstOrFail();
                $linea = new MaterialCaso;
                $linea->forceFill(['caso_historico_id' => $caso->id, 'posicion' => $indice + 1, 'material_id' => $material->id, 'unidad_id' => $material->unidad_id,
                    'material_nombre' => $material->nombre, 'unidad_nombre' => $material->unidad->nombre, 'componente' => 'General', 'cantidad_total' => $fila['cantidad_total'],
                    'costo_unitario_estimado' => $fila['costo_unitario_estimado_bs'], 'subtotal_estimado' => (string) BigDecimal::of($fila['cantidad_total'])->multipliedBy($fila['costo_unitario_estimado_bs'])->toScale(2, RoundingMode::HALF_UP), 'fuente_costo' => 'estimacion_inicial'])->save();
            }
        }
    }
}
