<?php

namespace Tests\Feature;

use Database\Seeders\ConsumosMaterialesSeeder;
use PHPUnit\Framework\TestCase;

class ConsumosMaterialesTest extends TestCase
{
    private function mueble(array $cambios = []): array
    {
        return $cambios + ['tipo_nombre' => 'Mesa', 'material_principal_nombre' => 'Madera', 'largo_cm' => 160, 'ancho_cm' => 90, 'alto_cm' => 75, 'cantidad_muebles' => 1, 'puertas' => 0, 'cajones' => 0, 'divisiones' => 0, 'acabado' => 'Barnizado natural'];
    }

    public function test_mesas_cercanas_comparten_base_y_consumibles_crecen_con_superficie(): void
    {
        $s = new ConsumosMaterialesSeeder;
        $p = $s->calcular($this->mueble(['ancho_cm' => 80]));
        $n = $s->calcular($this->mueble());
        $g = $s->calcular($this->mueble(['largo_cm' => 180, 'alto_cm' => 76]));
        foreach ([$p, $n, $g] as $r) {
            $this->assertSame(4, $r['materiales']['Madera']);
            $this->assertGreaterThanOrEqual($r['volumen_m3'] * 1.2, $r['materiales']['Madera'] * .024 + 0.000001);
            foreach (['Barniz', 'Sellador para madera', 'Disolvente', 'Cola para madera', 'Lija'] as $nombre) {
                $this->assertGreaterThan(0, (float) $r['materiales'][$nombre]);
            }
        }
        $this->assertGreaterThan((float) $p['materiales']['Barniz'], (float) $g['materiales']['Barniz']);
        $this->assertSame($n, $s->calcular($this->mueble(['cantidad_muebles' => 6])));
    }

    public function test_sin_acabado_no_incluye_pinturas_y_melamina_cantea_bordes_visibles(): void
    {
        $s = new ConsumosMaterialesSeeder;
        $c = $s->calcular($this->mueble(['tipo_nombre' => 'Silla', 'largo_cm' => 45, 'ancho_cm' => 45, 'alto_cm' => 90, 'acabado' => 'Sin acabado']));
        $this->assertSame(1, $c['materiales']['Madera']);
        $this->assertArrayNotHasKey('Barniz', $c['materiales']);
        $this->assertArrayNotHasKey('Sellador para madera', $c['materiales']);
        $v = $s->calcular($this->mueble(['tipo_nombre' => 'Velador', 'material_principal_nombre' => 'Melamina', 'largo_cm' => 40, 'ancho_cm' => 35, 'alto_cm' => 50, 'cajones' => 1, 'acabado' => 'Melamina']));
        $this->assertSame(1, $v['materiales']['Melamina']);
        $this->assertSame(1, $v['materiales']['Juego de correderas']);
        $this->assertLessThan(6, (float) $v['materiales']['Canto PVC']);
        $this->assertArrayNotHasKey('Barniz', $v['materiales']);
    }

    public function test_herrajes_corresponden_a_cajones_puertas_y_repisas(): void
    {
        $s = new ConsumosMaterialesSeeder;
        $c = $s->calcular($this->mueble(['tipo_nombre' => 'Cómoda', 'largo_cm' => 100, 'ancho_cm' => 45, 'alto_cm' => 90, 'cajones' => 4]));
        $this->assertSame(4, $c['materiales']['Juego de correderas']);
        $this->assertSame(4, $c['materiales']['Pomo de madera']);
        $this->assertArrayNotHasKey('Bisagra', $c['materiales']);
        $v = $s->calcular($this->mueble(['tipo_nombre' => 'Vitrina', 'largo_cm' => 100, 'ancho_cm' => 40, 'alto_cm' => 180, 'puertas' => 2, 'divisiones' => 3]));
        $this->assertSame(2, $v['materiales']['Vidrio para vitrina']);
        $this->assertSame(12, $v['materiales']['Soporte de repisa']);
        $this->assertGreaterThanOrEqual(4, $v['materiales']['Bisagra']);
    }

    public function test_tornillos_se_ajustan_al_montaje_del_mueble_y_sus_herrajes(): void
    {
        $s = new ConsumosMaterialesSeeder;
        $mesa = $s->calcular($this->mueble());
        $this->assertSame(12, $mesa['materiales']['Tornillo para madera']);
        $this->assertSame(['estructura' => 12, 'repisas_fijas' => 0, 'cajones_y_correderas' => 0, 'bisagras' => 0], $mesa['montaje_tornillos']);
        $silla = $s->calcular($this->mueble(['tipo_nombre' => 'Silla', 'largo_cm' => 45, 'ancho_cm' => 45, 'alto_cm' => 90]));
        $this->assertSame(4, $silla['materiales']['Tornillo para madera']);
        $comoda = $s->calcular($this->mueble(['tipo_nombre' => 'Cómoda', 'largo_cm' => 100, 'ancho_cm' => 45, 'alto_cm' => 90, 'cajones' => 4]));
        $this->assertSame(56, $comoda['materiales']['Tornillo para madera']);
        $this->assertSame(48, $comoda['montaje_tornillos']['cajones_y_correderas']);
        $velador = $s->calcular($this->mueble(['tipo_nombre' => 'Velador', 'material_principal_nombre' => 'Melamina', 'largo_cm' => 40, 'ancho_cm' => 35, 'alto_cm' => 50, 'cajones' => 1, 'acabado' => 'Melamina']));
        $this->assertSame(28, $velador['materiales']['Tornillo confirmat']);
        $estante = $s->calcular($this->mueble(['tipo_nombre' => 'Estante', 'largo_cm' => 90, 'ancho_cm' => 35, 'alto_cm' => 160, 'divisiones' => 4]));
        $this->assertSame(0, $estante['montaje_tornillos']['repisas_fijas']);
        $this->assertSame(16, $estante['materiales']['Soporte de repisa']);
    }

    public function test_rechaza_un_panel_que_no_cabe_en_el_tablero(): void
    {
        $this->expectException(\RuntimeException::class);
        (new ConsumosMaterialesSeeder)->calcular($this->mueble(['material_principal_nombre' => 'Melamina', 'largo_cm' => 400, 'ancho_cm' => 200, 'acabado' => 'Melamina']));
    }
}
