<?php

namespace App\Services;

use App\Models\ExistenciaMaterial;
use App\Models\Material;
use App\Models\MovimientoMaterial;
use App\Models\User;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ExistenciaMaterialService
{
    /** Cantidades en la unidad del material, sin presentaciones ni cambio de costos. */
    public function guardarCantidad(User $actor, Material $material, array $datos, bool $corregir = false): MovimientoMaterial
    {
        Gate::forUser($actor->fresh())->authorize('cantidades', $material);
        Validator::make($datos, [
            'clave' => 'required|uuid',
            'cantidad' => 'required',
            'unidad_id' => 'required|integer',
            'existencia_esperada' => 'required',
            'motivo' => 'required|string|min:5|max:2000',
        ])->validate();
        $cantidad = (string) Decimal::cantidad($datos['cantidad'], $material->unidad, 'cantidad', ! $corregir);
        $esperada = (string) Decimal::cantidad($datos['existencia_esperada'], $material->unidad, 'existencia_esperada', false);
        $clave = strtolower($datos['clave']);
        $motivo = trim($datos['motivo']);
        Validator::make(['motivo' => $motivo], ['motivo' => 'required|string|min:5|max:2000'])->validate();
        $hash = hash('sha256', json_encode([
            'usuario' => $actor->id, 'material' => $material->id, 'corregir' => $corregir,
            'cantidad' => $cantidad, 'esperada' => $esperada, 'unidad' => (int) $datos['unidad_id'], 'motivo' => $motivo,
        ], JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($actor, $material, $datos, $cantidad, $esperada, $clave, $motivo, $hash, $corregir) {
                $material = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor->fresh())->authorize('cantidades', $material);
                $movimiento = MovimientoMaterial::where('clave', $clave)->first();
                if ($movimiento) {
                    return $this->repetida($movimiento, $hash);
                }
                if ((int) $datos['unidad_id'] !== $material->unidad_id) {
                    throw ValidationException::withMessages(['cantidad' => 'La unidad cambió. Vuelve a abrir el formulario.']);
                }
                $saldo = ExistenciaMaterial::where('material_id', $material->id)->lockForUpdate()->first();
                $antes = BigDecimal::of($saldo?->cantidad ?? '0');
                if ($corregir && ! $antes->isEqualTo($esperada)) {
                    throw ValidationException::withMessages(['cantidad' => 'La existencia cambió. Vuelve a abrir el formulario y revisa el saldo.']);
                }
                $despues = Decimal::cantidad($corregir ? $cantidad : (string) $antes->plus($cantidad), $material->unidad, 'cantidad', false);
                $diferencia = $despues->minus($antes);
                if ($diferencia->isZero()) {
                    throw ValidationException::withMessages(['cantidad' => 'La cantidad ya coincide con la existencia. No hay cambios que registrar.']);
                }
                $tipo = $diferencia->isNegative() ? 'ajuste_negativo' : 'ajuste_positivo';
                $movimiento = new MovimientoMaterial;
                $movimiento->clave = $clave;
                $movimiento->solicitud_hash = $hash;
                $movimiento->usuario_id = $actor->id;
                $movimiento->motivo = $motivo;
                $movimiento->material_id = $material->id;
                $movimiento->unidad_id = $material->unidad_id;
                $movimiento->material_nombre = $material->nombre;
                $movimiento->unidad_codigo = $material->unidad->codigo;
                $movimiento->tipo = $tipo;
                $movimiento->cantidad = (string) $diferencia->abs();
                $movimiento->existencia_anterior = (string) $antes;
                $movimiento->existencia_resultante = (string) $despues;
                $movimiento->precio_referencia_historico = $material->precio_referencia;
                $movimiento->estado_costo = 'no_aplica';
                $movimiento->save();

                $saldo ??= new ExistenciaMaterial;
                $saldo->material_id = $material->id;
                $saldo->cantidad = (string) $despues;
                $saldo->save();

                return $movimiento;
            }, 5);
        } catch (QueryException $error) {
            $movimiento = MovimientoMaterial::where('clave', $clave)->first();
            if ($movimiento) {
                return $this->repetida($movimiento, $hash);
            }
            throw $error;
        }
    }

    private function repetida(MovimientoMaterial $movimiento, string $hash): MovimientoMaterial
    {
        if (! hash_equals($movimiento->solicitud_hash, $hash)) {
            throw ValidationException::withMessages(['clave' => 'Esta clave ya se utilizó con otros datos. No se registró otro movimiento.']);
        }

        return $movimiento;
    }

}
