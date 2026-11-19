<?php

namespace Tests\Feature;

use App\Support\Decimal;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;

class DecimalEntradaTest extends TestCase
{
    public function test_formato_de_entrada_conserva_el_valor_exacto_sin_ceros_sobrantes(): void
    {
        foreach (['20.0000' => '20', '20.5000' => '20.5', '0.0000' => '0', '0.000100' => '0.0001', '125.250000' => '125.25', '1234567890123456.123450' => '1234567890123456.12345'] as $entrada => $esperado) {
            $resultado = Decimal::entrada($entrada);
            $this->assertSame($esperado, $resultado);
            $this->assertTrue(BigDecimal::of($entrada)->isEqualTo($resultado));
        }
    }

    public function test_costo_desconocido_sigue_vacio_y_cero_sigue_siendo_cero(): void
    {
        $this->assertSame('', Decimal::entrada(null));
        $this->assertSame('', Decimal::entrada(''));
        $this->assertSame('0', Decimal::entrada(0));
        $this->assertSame('20', Decimal::entrada(20));
    }

    public function test_no_convierte_textos_invalidos_ni_redondea_decimales_significativos(): void
    {
        foreach (['20,50', '1e4', 'abc', ' 20.5000 ', '20.00001'] as $texto) {
            $this->assertSame($texto, Decimal::entrada($texto));
        }
    }
}
