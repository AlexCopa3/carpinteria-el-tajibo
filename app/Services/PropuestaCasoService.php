<?php

namespace App\Services;

use App\Models\CasoHistorico;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class PropuestaCasoService
{
    /** El primer vecino es el principal. No se promedian casos ni se escalan dimensiones. */
    public function preparar(User $actor, array $mueble, array $resultado): array
    {
        $propuestas = [];
        foreach ($resultado['resultados'] as $indice => $referencia) {
            $caso = CasoHistorico::with('materiales.material.unidad')->findOrFail($referencia['id']);
            $advertencias = [];
            if ($caso->material_principal_id !== (int) $mueble['material_principal_id']) {
                $advertencias[] = 'El material principal es distinto. No se sustituye automáticamente: usa el llenado manual o busca con ese material.';
            }
            $cantidadAnterior = BigDecimal::of($caso->cantidad_muebles);
            $cantidadNueva = BigDecimal::of($mueble['cantidad_muebles']);
            $agrupados = [];
            foreach ($caso->materiales as $linea) {
                if (! BigDecimal::of($linea->cantidad_total)->isPositive()) {
                    continue;
                }
                $material = $linea->material;
                if (! $material || ! $material->activo || ! $material->unidad || $material->unidad_id !== $linea->unidad_id) {
                    $advertencias[] = 'Un material del caso ya no está activo o cambió de unidad; requiere sustitución manual.';

                    continue;
                }
                $id = $material->id;
                if (! isset($agrupados[$id])) {
                    $agrupados[$id] = ['material' => $material, 'cantidad' => BigDecimal::zero(), 'costo_historico' => BigDecimal::zero(), 'historico_pendiente' => false];
                }
                $agrupados[$id]['cantidad'] = $agrupados[$id]['cantidad']->plus($linea->cantidad_total);
                $conocidoHistorico = $linea->costo_unitario_estimado !== null && BigDecimal::of($linea->costo_unitario_estimado)->isPositive();
                $agrupados[$id]['historico_pendiente'] = $agrupados[$id]['historico_pendiente'] || ! $conocidoHistorico;
                if ($conocidoHistorico) {
                    $agrupados[$id]['costo_historico'] = $agrupados[$id]['costo_historico']->plus(BigDecimal::of($linea->cantidad_total)->multipliedBy($linea->costo_unitario_estimado));
                }
            }
            $materiales = [];
            $costoActual = BigDecimal::zero();
            $costoHistorico = BigDecimal::zero();
            $historicoPendiente = false;
            $pendiente = false;
            $redondeadas = false;
            foreach ($agrupados as $id => $grupo) {
                $material = $grupo['material'];
                $escala = $material->unidad->fraccionaria ? 4 : 0;
                $unitaria = $grupo['cantidad']->dividedBy($cantidadAnterior, $escala, RoundingMode::CEILING);
                $redondeadas = $redondeadas || ! $unitaria->multipliedBy($cantidadAnterior)->isEqualTo($grupo['cantidad']);
                // Mismas cantidades adaptadas en ambos escenarios. Si hay varias líneas del
                // mismo material, conservar su costo ponderado por cantidad, sin usar floats.
                $historicoPendiente = $historicoPendiente || $grupo['historico_pendiente'];
                if (! $grupo['historico_pendiente']) {
                    $subtotalHistorico = BigRational::of($grupo['costo_historico'])
                        ->dividedBy($grupo['cantidad'])
                        ->multipliedBy($unitaria)->multipliedBy($cantidadNueva)
                        ->toScale(2, RoundingMode::HALF_UP);
                    $costoHistorico = $costoHistorico->plus($subtotalHistorico);
                }
                $precio = $material->precio_referencia;
                $conocido = $precio !== null && BigDecimal::of($precio)->isPositive();
                $subtotal = $conocido ? $unitaria->multipliedBy($cantidadNueva)->multipliedBy($precio)->toScale(2, RoundingMode::HALF_UP) : null;
                $pendiente = $pendiente || ! $conocido;
                if ($subtotal !== null) {
                    $costoActual = $costoActual->plus($subtotal);
                }
                $materiales[] = [
                    'material_id' => $id,
                    'nombre' => $material->nombre,
                    'unidad' => $material->unidad->nombre,
                    'componente' => 'General',
                    'cantidad_unitaria' => (string) $unitaria,
                    'costo_unitario_estimado' => $conocido ? $precio : null,
                    'estado_costo' => $conocido ? 'conocido' : 'pendiente',
                    'fuente_costo' => 'referencia_catalogo',
                ];
            }
            if (count($materiales) > 50 || ! $materiales) {
                $advertencias[] = 'Los materiales del antecedente requieren revisión manual.';
            }
            $manoUnitaria = BigDecimal::of($caso->mano_obra_estimada_total)->dividedBy($cantidadAnterior, 2, RoundingMode::HALF_UP);
            // Otros costos son un total fijo del detalle, no se multiplican por muebles.
            $otros = BigDecimal::of($caso->otros_costos_estimados_total);
            $totalActual = $pendiente ? null : (string) $costoActual->plus($manoUnitaria->multipliedBy($cantidadNueva))->plus($otros)->toScale(2);
            $totalHistorico = $historicoPendiente ? null : (string) $costoHistorico->plus($manoUnitaria->multipliedBy($cantidadNueva))->plus($otros)->toScale(2);
            $procedencia = [
                'version' => 1, 'actor_id' => $actor->id, 'caso_id' => $caso->id,
                'origen' => $caso->origen,
                'perfil' => $resultado['perfil'], 'caracteristicas_consultadas' => $mueble,
                'porcentaje' => $referencia['porcentaje'], 'consultado_at' => now()->toIso8601String(),
                'referencia_economica' => ['cantidad_solicitada' => (int) $mueble['cantidad_muebles'], 'cantidad_original' => $caso->cantidad_muebles, 'historico_adaptado_total' => $totalHistorico, 'actual_total' => $totalActual],
            ];
            $propuestas[] = [
                'caso_id' => $caso->id,
                'principal' => $indice === 0,
                'materiales' => $materiales,
                'mano_obra_unitaria' => (string) $manoUnitaria,
                'otros_costos_total' => (string) $otros->toScale(2),
                'costo_actual_total' => $totalActual,
                'costo_historico_adaptado_total' => $totalHistorico,
                'lote_diferente' => ! $cantidadAnterior->isEqualTo($cantidadNueva),
                'costo_historico_grupo' => $caso->costo_estimado_total,
                'precio_historico_grupo' => $caso->precio_historico_total,
                'cantidad_original' => $caso->cantidad_muebles,
                'cantidad_solicitada' => (int) $mueble['cantidad_muebles'],
                'advertencias' => array_values(array_unique($advertencias)),
                'redondeadas' => $redondeadas,
                'aplicable' => ! $advertencias,
                'referencia_caso' => Crypt::encryptString(json_encode($procedencia, JSON_THROW_ON_ERROR)),
            ];
        }

        return $propuestas;
    }

    /** La referencia acredita procedencia, nunca autoriza importes o aprobación. */
    public function verificarReferencia(User $actor, string $token): array
    {
        try {
            $referencia = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $error) {
            throw ValidationException::withMessages(['referencia_caso' => 'La referencia fue alterada. Vuelve a buscar y elegir un caso.']);
        }
        if (! is_array($referencia) || ($referencia['version'] ?? null) !== 1 || ($referencia['actor_id'] ?? null) !== $actor->id) {
            throw ValidationException::withMessages(['referencia_caso' => 'La referencia no pertenece a esta sesión de trabajo. Vuelve a buscar.']);
        }

        return $referencia;
    }
}
