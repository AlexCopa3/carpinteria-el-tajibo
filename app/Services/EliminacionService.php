<?php

namespace App\Services;

use App\Models\CasoHistorico;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\Material;
use App\Models\MaterialCaso;
use App\Models\MaterialPedido;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EliminacionService
{
    public function eliminar(User $actor, Model $registro, ?int $version = null): void
    {
        abort_unless(in_array($registro::class, [Cliente::class, Material::class, Categoria::class, Cotizacion::class], true), 403);
        Gate::forUser($actor)->authorize('delete', $registro);
        try {
            DB::transaction(function () use ($actor, $registro, $version) {
                $registro = $registro->newQuery()->whereKey($registro->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor->fresh())->authorize('delete', $registro);
                if ($registro instanceof Cotizacion && $version !== $registro->version) {
                    throw ValidationException::withMessages(['version' => 'La cotización cambió. Recarga antes de eliminarla.']);
                }
                $motivo = $this->motivo($registro);
                if ($motivo !== null) {
                    throw ValidationException::withMessages(['registro' => $motivo]);
                }
                $auditoria = app(AuditoriaService::class);
                $antes = $auditoria->datos($registro);
                if ($registro instanceof Cotizacion) {
                    $antes['detalles'] = $registro->detalles()->with('materiales')->get()->toArray();
                }
                $auditoria->registrar($actor, $registro, 'eliminado', $antes, []);
                if ($registro instanceof Cotizacion) {
                    foreach ($registro->detalles()->get() as $detalle) {
                        $detalle->materiales()->delete();
                        $detalle->delete();
                    }
                }
                if ($registro instanceof Material) {
                    DB::table('existencias_materiales')->where('material_id', $registro->id)->delete();
                }
                $registro->delete();
            }, 3);
        } catch (QueryException $error) {
            if (str_starts_with((string) $error->getCode(), '23')) {
                throw ValidationException::withMessages(['registro' => $registro instanceof Cotizacion ? 'La cotización está relacionada con otros registros y debe conservarse.' : 'El registro está relacionado con otros datos. Desactívalo en lugar de eliminarlo.']);
            }
            throw $error;
        }
    }

    public function motivo(Model $registro): ?string
    {
        if ($registro instanceof Cotizacion) {
            if ($registro->pedido()->exists()) {
                return 'Esta cotización tiene un pedido y debe conservarse.';
            }
            if ($registro->aprobado_at !== null || ! in_array($registro->estado, ['borrador', 'pendiente_costos'], true)) {
                return 'Solo se eliminan cotizaciones preliminares, sin aprobación ni pedido.';
            }

            return null;
        }
        if ($registro instanceof Cliente && ($registro->pedidos()->exists() || $registro->cotizaciones()->exists())) {
            return 'Este cliente tiene registros relacionados. Desactívalo para conservar sus datos.';
        }
        if ($registro instanceof Categoria && $registro->materiales()->exists()) {
            return 'Esta categoría tiene materiales asociados. Desactívala en lugar de eliminarla.';
        }
        if ($registro instanceof Material) {
            $saldo = $registro->existencia()->first();
            $vinculado = ($saldo && ! BigDecimal::of($saldo->cantidad)->isZero())
                || $registro->movimientos()->exists() || $registro->cambios()->exists()
                || $registro->materialesCotizacion()->exists()
                || DetalleCotizacion::where('material_principal_id', $registro->id)->exists()
                || MaterialPedido::where('material_id', $registro->id)->exists()
                || CasoHistorico::where('material_principal_id', $registro->id)->exists()
                || MaterialCaso::where('material_id', $registro->id)->exists();
            if ($vinculado) {
                return 'Este material tiene existencias, usos o historial. Desactívalo para conservar sus datos.';
            }
        }

        return null;
    }
}
