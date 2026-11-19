<?php

namespace App\Services;

use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class ComparadorCasosService
{
    public const NUMERICOS = ['largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones', 'cantidad_muebles'];

    public const ETIQUETAS = ['largo_cm' => 'Largo (cm)', 'ancho_cm' => 'Ancho (cm)', 'alto_cm' => 'Alto (cm)', 'material_principal_id' => 'Material principal', 'acabado' => 'Acabado', 'puertas' => 'Puertas', 'cajones' => 'Cajones', 'divisiones' => 'Divisiones', 'cantidad_muebles' => 'Cantidad de muebles'];

    public function numero(array $datos, string $campo)
    {
        if (! array_key_exists($campo, $datos) || $datos[$campo] === null) {
            throw ValidationException::withMessages([$campo => 'Falta una característica necesaria para comparar.']);
        }

        return Decimal::numero($datos[$campo], in_array($campo, ['largo_cm', 'ancho_cm', 'alto_cm'], true) ? 4 : 0, $campo, in_array($campo, ['largo_cm', 'ancho_cm', 'alto_cm', 'cantidad_muebles'], true));
    }

    public function fueraDeCobertura(array $consulta, array $rangos): array
    {
        $fuera = [];
        foreach (self::NUMERICOS as $campo) {
            $valor = $this->numero($consulta, $campo);
            if (! isset($rangos[$campo]['min'],$rangos[$campo]['max'])) {
                throw new \RuntimeException('Rangos incompletos.');
            }
            if ($valor->isLessThan($rangos[$campo]['min']) || $valor->isGreaterThan($rangos[$campo]['max'])) {
                $fuera[] = self::ETIQUETAS[$campo];
            }
        }

        return $fuera;
    }

    public function comparar(array $consulta, array $referencia, array $rangos, array $pesos): array
    {
        if (($consulta['tipo_mueble_id'] ?? null) !== ($referencia['tipo_mueble_id'] ?? null)) {
            throw new \InvalidArgumentException('Solo se comparan muebles del mismo tipo.');
        }
        if (array_diff(array_keys(self::ETIQUETAS), array_keys($pesos)) || array_diff(array_keys($pesos), array_keys(self::ETIQUETAS)) || array_sum($pesos) !== 100) {
            throw new \RuntimeException('Pesos incompletos o suma distinta de 100.');
        }
        $perdida = BigRational::zero();
        $diferencias = [];
        foreach ($pesos as $campo => $peso) {
            if (! is_int($peso) || $peso <= 0) {
                throw new \RuntimeException('Peso inválido.');
            }
            if (in_array($campo, self::NUMERICOS, true)) {
                $solicitado = $this->numero($consulta, $campo);
                $historico = $this->numero($referencia, $campo);
                $rango = BigDecimal::of($rangos[$campo]['max'])->minus($rangos[$campo]['min']);
                if ($rango->isNegative()) {
                    throw new \RuntimeException('Rango inválido.');
                }
                $delta = $solicitado->minus($historico)->abs();
                $distancia = $rango->isZero() ? BigRational::of($delta->isZero() ? 0 : 1) : ($delta->isGreaterThan($rango) ? BigRational::of(1) : BigRational::of($delta)->dividedBy($rango));
                $textoConsulta = Decimal::mostrar($solicitado);
                $textoReferencia = Decimal::mostrar($historico);
            } else {
                foreach ([$consulta, $referencia] as $datos) {
                    if (! isset($datos[$campo]) || ($campo === 'acabado' && (! is_string($datos[$campo]) || trim($datos[$campo]) === '')) || ($campo === 'material_principal_id' && (! is_int($datos[$campo]) || $datos[$campo] < 1))) {
                        throw ValidationException::withMessages([$campo => 'Falta una característica válida para comparar.']);
                    }
                }
                $distancia = BigRational::of($consulta[$campo] === $referencia[$campo] ? 0 : 1);
                $textoConsulta = (string) ($campo === 'material_principal_id' ? ($consulta['material_principal_nombre'] ?? $consulta[$campo]) : $consulta[$campo]);
                $textoReferencia = (string) ($campo === 'material_principal_id' ? ($referencia['material_principal_nombre'] ?? $referencia[$campo]) : $referencia[$campo]);
            }
            $perdida = $perdida->plus($distancia->multipliedBy($peso));
            $diferencias[] = ['campo' => $campo, 'nombre' => self::ETIQUETAS[$campo], 'solicitado' => $textoConsulta, 'referencia' => $textoReferencia, 'coincide' => $distancia->isZero()];
        }
        $puntaje = BigRational::of(100)->minus($perdida);

        return ['puntaje' => $puntaje, 'porcentaje' => (string) $puntaje->toScale(2,RoundingMode::HALF_UP), 'diferencias' => $diferencias];
    }
}
