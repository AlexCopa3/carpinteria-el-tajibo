<?php

namespace Tests\Feature;

use App\Services\ComparadorCasosService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComparadorSimilitudTest extends TestCase
{
    private function datos(): array
    {
        return ['tipo_mueble_id' => 1, 'material_principal_id' => 1, 'material_principal_nombre' => 'Madera', 'acabado' => 'Barnizado natural', 'largo_cm' => '100', 'ancho_cm' => '80', 'alto_cm' => '75', 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'cantidad_muebles' => 1];
    }

    private function rangos(): array
    {
        $r = [];
        foreach (ComparadorCasosService::NUMERICOS as $f) {
            $r[$f] = ['min' => $this->datos()[$f], 'max' => $this->datos()[$f]];
        }$r['largo_cm'] = ['min' => '100', 'max' => '115'];
        $r['cantidad_muebles'] = ['min' => '1', 'max' => '2'];

        return $r;
    }

    public function test_identidad_simetria_limites_y_categorias_no_ordinales(): void
    {
        $s = app(ComparadorCasosService::class);
        $a = $this->datos();
        $pesos = config('similitud.pesos');
        $this->assertSame('100.00', $s->comparar($a, $a, $this->rangos(), $pesos)['porcentaje']);
        $b = [...$a, 'material_principal_id' => 99999];
        $this->assertSame('75.00', $s->comparar($a, $b, $this->rangos(), $pesos)['porcentaje']);
        $this->assertSame('75.00', $s->comparar($b, $a, $this->rangos(), $pesos)['porcentaje']);
        $b = [...$a, 'puertas' => 1];
        $this->assertSame('95.00', $s->comparar($a, $b, $this->rangos(), $pesos)['porcentaje']);
    }

    public function test_umbral_se_compara_antes_de_redondear(): void
    {
        $s = app(ComparadorCasosService::class);
        $a = $this->datos();
        $b = [...$a, 'largo_cm' => '105.0001', 'cantidad_muebles' => 2, 'acabado' => 'Pintado'];
        $r = $s->comparar($a, $b, $this->rangos(), config('similitud.pesos'));
        $this->assertSame('80.00', $r['porcentaje']);
        $this->assertTrue($r['puntaje']->isLessThan('80'));
        $b['largo_cm'] = '105';
        $r = $s->comparar($a, $b, $this->rangos(), config('similitud.pesos'));
        $this->assertTrue($r['puntaje']->isEqualTo('80'));
    }

    public function test_caracteristica_ausente_no_se_convierte_en_cero(): void
    {
        $a = $this->datos();
        unset($a['puertas']);
        $this->expectException(ValidationException::class);
        app(ComparadorCasosService::class)->comparar($a, $this->datos(), $this->rangos(), config('similitud.pesos'));
    }

    public function test_tipo_distinto_no_se_compara(): void
    {
        $b = [...$this->datos(), 'tipo_mueble_id' => 2];
        $this->expectException(\InvalidArgumentException::class);
        app(ComparadorCasosService::class)->comparar($this->datos(),$b,$this->rangos(),config('similitud.pesos'));
    }
}
