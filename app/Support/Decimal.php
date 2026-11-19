<?php

namespace App\Support;

use App\Models\UnidadMaterial;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class Decimal
{
    public static function numero(mixed $valor, int $escala, string $campo, bool $positivo = false): BigDecimal
    {
        if ((! is_string($valor) && ! is_int($valor)) || ! preg_match('/^\d{1,16}(?:\.\d{1,10})?$/D', (string) $valor)) {
            throw ValidationException::withMessages([$campo => 'Introduce un decimal válido con punto, sin exponentes ni signos.']);
        }
        try {
            $n = BigDecimal::of((string) $valor)->toScale($escala, RoundingMode::UNNECESSARY);
        } catch (\Throwable) {
            throw ValidationException::withMessages([$campo => "El valor admite hasta $escala decimales sin redondeo."]);
        }
        $max = match ($escala) {
            4 => '99999999999999.9999',6 => '999999999999.999999',2 => '9999999999999999.99',10 => '99999999999999.9999999999',default => '99999999999999'
        };
        if ($n->isNegative() || ($positivo && ! $n->isPositive()) || $n->isGreaterThan($max)) {
            throw ValidationException::withMessages([$campo => 'El valor está fuera del intervalo permitido.']);
        }

        return $n;
    }

    public static function cantidad(mixed $valor, UnidadMaterial $unidad, string $campo = 'cantidad', bool $positivo = true): BigDecimal
    {
        $n = self::numero($valor, 4, $campo, $positivo);
        if (! $unidad->fraccionaria) {
            try {
                $n->toScale(0, RoundingMode::UNNECESSARY);
            } catch (\Throwable) {
                throw ValidationException::withMessages([$campo => 'Esta unidad de inventario requiere cantidades enteras.']);
            }
        }

        return $n;
    }

    /** Formato editable: punto decimal, sin redondeo ni ceros sobrantes. */
    public static function entrada(mixed $valor): string
    {
        if ($valor === null || ! is_scalar($valor)) {
            return '';
        }
        $texto = (string) $valor;
        if (preg_match('/^\d+\.\d+$/D', $texto)) {
            return rtrim(rtrim($texto, '0'), '.');
        }

        return $texto;
    }

    public static function mostrar(mixed $n, int $decimales = 4): string
    {
        if ($n === null) {
            return '—';
        }

        $texto = (string) BigDecimal::of((string) $n)->toScale($decimales, RoundingMode::HALF_UP);
        if ($decimales > 2) {
            [$entero, $fraccion] = explode('.', $texto);
            $fraccion = rtrim($fraccion, '0');
            if ($decimales !== 4) {
                $fraccion = str_pad($fraccion, 2, '0');
            }
            $texto = $entero.($fraccion !== '' ? '.'.$fraccion : '');
        }

        return str_replace('.', ',', $texto);
    }
}
