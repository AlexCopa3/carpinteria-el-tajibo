<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\TipoMueble;
use App\Services\CotizacionService;
use Illuminate\Database\Seeder;

class CotizacionesSeeder extends Seeder
{
    public function run(array &$carga = []): void
    {
        if (! isset($carga['lote'], $carga['propietario'])) {
            throw new \RuntimeException('Ejecuta DatosSistemaSeeder para respetar el orden y el control de duplicados.');
        }
        $propietario = $carga['propietario'];
        $materiales = $carga['materiales'];
        $bases = $carga['bases'];
        $cotizaciones = [];
        if ($carga['lote'] === DatosSistemaSeeder::PRIMERA_CARGA) {
            $escenarios = [
                0 => ['escritorio', 'Melamina', 120, 60, 75, 1, 1, 0, 2, 0, 'Melamina', '120'],
                1 => ['mesa', 'Madera', 140, 80, 75, 1, 4, 0, 0, 0, 'Barnizado natural', '150'],
                2 => ['silla', 'Madera', 45, 45, 90, 4, 2, 0, 0, 0, 'Barnizado natural', '60'],
                4 => ['estante', 'Melamina', 90, 35, 180, 1, 2, 0, 0, 5, 'Melamina', '100'],
                5 => ['puerta', 'Madera', 90, 4, 210, 1, 5, 0, 0, 0, 'Barnizado natural', '180'],
                6 => ['mesa', 'Madera', 160, 90, 75, 1, 5, 0, 0, 0, 'Barnizado natural', '170'],
                7 => ['escritorio', 'Melamina', 140, 65, 75, 2, 2, 0, 3, 0, 'Melamina', '140'],
            ];
            foreach ($escenarios as $indice => $escenario) {
                $detalle = $this->detalle($escenario, $materiales);
                $autor = $propietario;
                $datos = ['clave_registro' => fake()->uuid(), 'fecha' => today()->format('Y-m-d'), 'observaciones' => 'Presupuesto de muebles para vivienda.', 'detalles' => [$detalle]];
                if ($indice === 6) {
                    $datos['detalles'][] = $this->detalle($escenarios[2], $materiales);
                }
                if ($indice >= 4) {
                    $datos['recargo_porcentaje'] = '20';
                } // Ejemplo individual; nunca porcentaje predeterminado.
                $cotizacion = app(CotizacionService::class)->guardar($autor, $datos);
                if ($indice >= 4) {
                    $cotizacion = app(CotizacionService::class)->aprobar($propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => $cotizacion->detalles->mapWithKeys(fn ($fila) => [$fila->id => $fila->precio_sugerido])->all()]);
                }
                if ($indice === 4) {
                    $cotizacion = app(CotizacionService::class)->rechazar($propietario, $cotizacion, [
                        'version' => $cotizacion->version, 'motivo' => 'El cliente decide postergar la compra del mueble.', 'confirmacion' => true,
                    ]);
                }
                $cotizaciones[$indice] = $cotizacion;
            }

        } else {
            $muebles = [
                0 => ['escritorio', 'Melamina', 120, 60, 75, 1, 2, 0, 2, 0, 'Melamina', '130'],
                1 => ['mesa', 'Madera', 140, 80, 75, 1, 4, 0, 0, 0, 'Barnizado natural', '160'],
                2 => ['silla', 'Madera', 45, 45, 90, 4, 2, 0, 0, 0, 'Barnizado natural', '65'],
                4 => ['estante', 'Melamina', 90, 35, 180, 2, 2, 0, 0, 5, 'Melamina', '110'],
                5 => ['puerta', 'Madera', 90, 4, 210, 2, 5, 0, 0, 0, 'Barnizado natural', '190'],
            ];
            for ($indice = 0; $indice < 18; $indice++) {
                if (! isset($muebles[$indice % 6])) {
                    continue;
                }
                [$tipo,$principal,$largo,$ancho,$alto,$cantidad,$consumo,$puertas,$cajones,$divisiones,$acabado,$mano] = $muebles[$indice % 6];
                // Variantes ilustrativas, no un cálculo automático de despiece.
                $largo += intdiv($indice, 6) * ($tipo === 'silla' ? 2 : 10);
                $base = $bases[$principal];
                $detalle = ['tipo_mueble_id' => TipoMueble::where('codigo', $tipo)->where('activo', true)->firstOrFail()->id, 'material_principal_id' => $base->id, 'largo_cm' => (string) $largo, 'ancho_cm' => (string) $ancho, 'alto_cm' => (string) $alto, 'cantidad_muebles' => $cantidad, 'puertas' => $puertas, 'cajones' => $cajones, 'divisiones' => $divisiones, 'acabado' => $acabado, 'mano_obra_unitaria' => $mano, 'otros_costos_total' => (string) (15 + ($indice % 4) * 5), 'observaciones' => 'Confirmar medidas y coordinar el acabado.', 'materiales' => [
                    $this->fila($base, (string) $consumo), $this->fila($materiales['Tornillo para madera'], '20'),
                ]];
                if ($puertas > 0) {
                    $detalle['materiales'][] = $this->fila($materiales['Bisagra de cazoleta'], (string) ($puertas * 2));
                }
                if ($cajones > 0) {
                    $detalle['materiales'][] = $this->fila($materiales['Tirador metálico'], (string) $cajones);
                }
                if ($principal === 'Madera') {
                    $detalle['materiales'][] = $this->fila($materiales['Barniz mate'], '0.5');
                }
                $datos = ['clave_registro' => fake()->uuid(), 'fecha' => today()->format('Y-m-d'), 'observaciones' => 'Presupuesto de mobiliario para vivienda.', 'detalles' => [$detalle]];
                if ($indice >= 6) {
                    $datos['recargo_porcentaje'] = ['15', '20', '25'][$indice % 3];
                }
                $cotizacion = app(CotizacionService::class)->guardar($propietario, $datos);
                if ($indice >= 6) {
                    $cotizacion = app(CotizacionService::class)->aprobar($propietario, $cotizacion, ['version' => $cotizacion->version, 'precios' => $cotizacion->detalles->mapWithKeys(fn ($fila) => [$fila->id => $fila->precio_sugerido])->all()]);
                }
                $cotizaciones[$indice] = $cotizacion;
            }

        }
        $carga['cotizaciones'] = $cotizaciones;
    }

    private function detalle(array $escenario, array $materiales): array
    {
        [$tipo,$principal,$largo,$ancho,$alto,$cantidad,$consumo,$puertas,$cajones,$divisiones,$acabado,$mano] = $escenario;
        $base = $materiales[$principal];

        return ['tipo_mueble_id' => TipoMueble::where('codigo', $tipo)->value('id'), 'material_principal_id' => $base->id, 'largo_cm' => (string) $largo, 'ancho_cm' => (string) $ancho, 'alto_cm' => (string) $alto, 'cantidad_muebles' => $cantidad, 'puertas' => $puertas, 'cajones' => $cajones, 'divisiones' => $divisiones, 'acabado' => $acabado, 'mano_obra_unitaria' => $mano, 'otros_costos_total' => '20', 'observaciones' => 'Revisar medidas antes de fabricar.', 'materiales' => [
            ['material_id' => $base->id, 'componente' => 'General', 'cantidad_unitaria' => (string) $consumo, 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo', 'costo_unitario_estimado' => $base->precio_referencia],
            ['material_id' => $materiales['Tornillo']->id, 'componente' => 'General', 'cantidad_unitaria' => '20', 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo', 'costo_unitario_estimado' => $materiales['Tornillo']->precio_referencia],
        ]];
    }

    private function fila(Material $material, string $cantidad): array
    {
        return ['material_id' => $material->id, 'componente' => 'General', 'cantidad_unitaria' => $cantidad, 'costo_unitario_estimado' => $material->precio_referencia, 'estado_costo' => 'conocido', 'fuente_costo' => 'referencia_catalogo'];
    }
}
