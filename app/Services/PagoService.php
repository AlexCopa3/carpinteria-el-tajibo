<?php

namespace App\Services;

use App\Http\Requests\Pagos\GuardarPagoRequest;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PagoService
{
    public function resumen(User $actor, Pedido $pedido): array
    {
        Gate::forUser($actor->fresh())->authorize('viewAny', Pago::class);

        return $this->totales($pedido);
    }

    private function totales(Pedido $pedido, ?int $excluirPago = null, bool $bloquear = false): array
    {
        $consulta = $pedido->pagos();
        if ($excluirPago !== null) {
            $consulta->where('id', '<>', $excluirPago);
        }
        if ($bloquear) {
            // Lectura actual bajo el bloqueo del pedido: no reutilizar el snapshot
            // que una comprobación de permisos pudo abrir antes de esperar el lock.
            $consulta->orderBy('id')->lockForUpdate();
        }
        $pagado = BigDecimal::zero();
        // Sumar strings DECIMAL, sin convertir importes a float (incluido SQLite).
        foreach ($consulta->get(['importe']) as $pago) {
            $pagado = $pagado->plus($pago->importe);
        }
        $precio = BigDecimal::of($pedido->precioVigente($bloquear));
        $saldo = $precio->minus($pagado);

        return ['precio' => (string) $precio->toScale(2), 'pagado' => (string) $pagado->toScale(2), 'saldo' => (string) $saldo->toScale(2),
            'estado' => $pagado->isZero() ? 'Sin pagos' : ($saldo->isZero() ? 'Pagado' : 'Pago parcial')];
    }

    public function guardar(User $actor, array $datos, ?Pago $pago = null): Pago
    {
        $actor = $actor->fresh();
        Gate::forUser($actor)->authorize($pago ? 'update' : 'create', $pago ?? Pago::class);
        if (isset($datos['motivo']) && is_string($datos['motivo'])) {
            $datos['motivo'] = trim($datos['motivo']);
        }
        $datos = PedidoService::validar($datos, GuardarPagoRequest::reglas($pago !== null));
        $datos['importe'] = (string) Decimal::numero($datos['importe'], 2, 'importe', true)->toScale(2);
        $datos['pedido_id'] = (int) $datos['pedido_id'];
        foreach (['referencia', 'observaciones'] as $campo) {
            $datos[$campo] = isset($datos[$campo]) && trim($datos[$campo]) !== '' ? trim($datos[$campo]) : null;
        }
        if ($pago === null) {
            $datos['clave_registro'] = strtolower($datos['clave_registro']);
        }
        $contenido = $datos;
        unset($contenido['clave_registro']);
        ksort($contenido);
        $hash = hash('sha256', json_encode([$actor->id, $contenido], JSON_THROW_ON_ERROR));

        try {
            return DB::transaction(function () use ($actor, $datos, $pago, $hash) {
                Gate::forUser($actor->fresh())->authorize($pago ? 'update' : 'create', $pago ?? Pago::class);
                $ids = array_values(array_unique([$datos['pedido_id'], ...($pago ? [$pago->pedido_id] : [])]));
                sort($ids, SORT_NUMERIC);
                $pedidos = Pedido::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                if ($pedidos->count() !== count($ids)) {
                    throw ValidationException::withMessages(['pedido_id' => 'El pedido ya no está disponible.']);
                }
                foreach ($pedidos as $pedido) {
                    Gate::forUser($actor->fresh())->authorize('view', $pedido);
                }
                $edicion = $pago !== null;
                if ($edicion) {
                    $actual = Pago::whereKey($pago->id)->lockForUpdate()->firstOrFail();
                    Gate::forUser($actor->fresh())->authorize('update', $actual);
                    if ($actual->pedido_id !== $pago->pedido_id || $actual->version !== (int) $datos['version']) {
                        throw ValidationException::withMessages(['version' => 'El pago cambió. Recarga su ficha antes de editar.']);
                    }
                } else {
                    $anterior = Pago::where('clave_registro', $datos['clave_registro'])->lockForUpdate()->first();
                    if ($anterior) {
                        return $this->repetido($actor, $anterior, $hash);
                    }
                    $actual = new Pago;
                    $actual->registrado_por_id = $actor->id;
                    $actual->clave_registro = $datos['clave_registro'];
                    $actual->hash_solicitud = $hash;
                    $actual->version = 0;
                }
                $pedido = $pedidos->get($datos['pedido_id']);
                $resumen = $this->totales($pedido, $edicion ? $actual->id : null, true);
                if (BigDecimal::of($datos['importe'])->isGreaterThan($resumen['saldo'])) {
                    throw ValidationException::withMessages(['importe' => 'El importe supera el saldo disponible de Bs '.Decimal::mostrar($resumen['saldo'], 2).'.']);
                }
                $antes = $edicion ? $this->datosAuditoria($actual) : [];
                $actual->pedido_id = $pedido->id;
                $actual->fecha = $datos['fecha'];
                $actual->importe = $datos['importe'];
                $actual->medio = $datos['medio'];
                $actual->referencia = $datos['referencia'];
                $actual->observaciones = $datos['observaciones'];
                $actual->version++;
                $actual->save();
                app(AuditoriaService::class)->registrar($actor, $actual, $edicion ? 'modificado' : 'creado', $antes,
                    [...$this->datosAuditoria($actual), ...($edicion ? ['motivo' => trim($datos['motivo'])] : [])]);

                return $actual->fresh(['pedido.cliente', 'registradoPor']);
            }, 5);
        } catch (QueryException $error) {
            if ($pago === null && isset($datos['clave_registro'])) {
                $anterior = Pago::where('clave_registro', $datos['clave_registro'])->first();
                if ($anterior) {
                    return $this->repetido($actor, $anterior, $hash);
                }
            }
            throw $error;
        }
    }

    private function repetido(User $actor, Pago $pago, string $hash): Pago
    {
        Gate::forUser($actor->fresh())->authorize('view', $pago);
        if ($pago->registrado_por_id !== $actor->id || ! hash_equals($pago->hash_solicitud, $hash)) {
            throw ValidationException::withMessages(['clave_registro' => 'La clave del pago ya se utilizó con otros datos.']);
        }

        // El pago proviene de una lectura actual con bloqueo; fresh() podría volver
        // al snapshot anterior y ocultar el registro creado por la otra transacción.
        return $pago->load(['pedido.cliente', 'registradoPor']);
    }

    private function datosAuditoria(Pago $pago): array
    {
        return $pago->only(['pedido_id', 'fecha', 'importe', 'medio', 'referencia', 'observaciones', 'registrado_por_id', 'version']);
    }
}
