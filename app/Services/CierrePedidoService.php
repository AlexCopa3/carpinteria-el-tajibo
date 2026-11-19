<?php

namespace App\Services;

use App\Http\Requests\Pedidos\ValidarCierrePedidoRequest;
use App\Models\EstadoPedido;
use App\Models\MaterialPedido;
use App\Models\Pedido;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CierrePedidoService
{
    public function validarMateriales(User $actor, Pedido $pedido, array $datos): Pedido
    {
        $datos = PedidoService::validar($datos, ValidarCierrePedidoRequest::reglas('validar'));

        return DB::transaction(function () use ($actor, $pedido, $datos) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('validarMateriales', $pedido);
            app(PedidoService::class)->version($pedido, $datos['version']);
            $this->comprobarMateriales($pedido);
            if ($pedido->materiales_validados_at && $pedido->materiales_validados_por_id) {
                return $pedido;
            }
            $antes = $pedido->only(['materiales_validados_por_id', 'materiales_validados_at', 'version']);
            $pedido->materiales_validados_por_id = $actor->id;
            $pedido->materiales_validados_at = now();
            $pedido->version++;
            $pedido->save();
            app(AuditoriaService::class)->registrar($actor, $pedido, 'materiales_validados', $antes, $pedido->only(array_keys($antes)));

            return $pedido;
        }, 5);
    }

    public function terminar(User $actor, Pedido $pedido, array $datos): Pedido
    {
        return $this->transicion($actor, $pedido, $datos, 'terminar');
    }

    public function entregar(User $actor, Pedido $pedido, array $datos): Pedido
    {
        return $this->transicion($actor, $pedido, $datos, 'entregar');
    }

    // Los servicios que cambian datos revisados llaman esto dentro del bloqueo y la transacción del pedido.
    public function invalidarMateriales(User $actor, Pedido $pedido): void
    {
        if (! $pedido->materiales_validados_at && ! $pedido->materiales_validados_por_id) {
            return;
        }
        $antes = $pedido->only(['materiales_validados_por_id', 'materiales_validados_at']);
        $pedido->materiales_validados_por_id = null;
        $pedido->materiales_validados_at = null;
        app(AuditoriaService::class)->registrar($actor, $pedido, 'validacion_materiales_invalidada', $antes, $pedido->only(array_keys($antes)));
    }

    private function comprobarMateriales(Pedido $pedido): void
    {
        if ($pedido->estado !== 'en_fabricacion') {
            PedidoService::error('estado', 'Solo puedes revisar un pedido en fabricación.');
        }
        $detalles = $pedido->detalles()->orderBy('id')->get();
        $filas = MaterialPedido::whereIn('detalle_pedido_id', $detalles->pluck('id'))->orderBy('id')->lockForUpdate()->get();
        if ($detalles->isEmpty() || $detalles->contains(fn ($detalle) => ! $filas->contains('detalle_pedido_id', $detalle->id))) {
            PedidoService::error('materiales', 'Cada mueble debe tener sus materiales registrados.');
        }
        foreach ($filas as $fila) {
            if ($fila->cantidad_utilizada === null || ! $fila->uso_registrado_por_id || ! $fila->uso_registrado_at || BigDecimal::of($fila->cantidad_utilizada)->isNegative()) {
                PedidoService::error('materiales', 'Completa las cantidades utilizadas antes de validar. Cero significa no utilizado; vacío sigue pendiente.');
            }
        }
    }

    private function comprobarPrecio(Pedido $pedido): void
    {
        $cotizacion = $pedido->cotizacion()->lockForUpdate()->firstOrFail();
        if (! $cotizacion->aprobado_at || $cotizacion->estado !== 'aprobada' || $cotizacion->precio_final === null
            || $pedido->cotizacion_aceptada_version !== $cotizacion->version) {
            PedidoService::error('precio', 'Aprueba la cotización y confirma la aceptación del cliente antes de terminar.');
        }
        $total = BigDecimal::zero();
        $detalles = $pedido->detalles()->with('detalleCotizacion')->orderBy('id')->lockForUpdate()->get();
        foreach ($detalles as $detalle) {
            $cotizado = $detalle->detalleCotizacion;
            if (! $cotizado || $cotizado->cotizacion_id !== $cotizacion->id || $cotizado->precio_final === null
                || $cotizado->cantidad_muebles !== $detalle->cantidad_muebles
                || ! BigDecimal::of($cotizado->precio_final)->isEqualTo($detalle->precio_acordado)) {
                PedidoService::error('precio', 'Los muebles y precios del pedido no coinciden con la cotización aceptada.');
            }
            $total = $total->plus($detalle->precio_acordado);
        }
        if ($detalles->isEmpty() || $detalles->count() !== $cotizacion->detalles()->count()
            || ! $total->isEqualTo($pedido->precio_total_acordado) || ! $total->isEqualTo($cotizacion->precio_final)) {
            PedidoService::error('precio', 'Revisa el total acordado y la cotización aprobada.');
        }
    }

    private function transicion(User $actor, Pedido $pedido, array $datos, string $accion): Pedido
    {
        $datos = PedidoService::validar($datos, ValidarCierrePedidoRequest::reglas($accion));
        $clave = strtolower($datos['clave_operacion']);
        $contenido = $datos;
        unset($contenido['clave_operacion']);
        ksort($contenido);
        $hash = hash('sha256', json_encode([$actor->id, $pedido->id, $accion, $contenido], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $pedido, $datos, $accion, $clave, $hash) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize($accion, $pedido);
            $evento = EstadoPedido::where('clave_operacion', $clave)->lockForUpdate()->first();
            if ($evento) {
                if ($evento->pedido_id !== $pedido->id || $evento->usuario_id !== $actor->id || ! hash_equals($evento->hash_solicitud, $hash)) {
                    PedidoService::error('clave_operacion', 'La clave ya fue utilizada con otros datos.');
                }

                return $pedido;
            }
            app(PedidoService::class)->version($pedido, $datos['version']);
            $antes = app(AuditoriaService::class)->datos($pedido);
            $anterior = $pedido->estado;
            if ($accion === 'terminar') {
                $this->comprobarMateriales($pedido);
                if (! empty($datos['validar_materiales'])) {
                    Gate::forUser($actor->fresh())->authorize('validarMateriales', $pedido);
                    if (! $pedido->materiales_validados_at || ! $pedido->materiales_validados_por_id) {
                        $pedido->materiales_validados_por_id = $actor->id;
                        $pedido->materiales_validados_at = now();
                    }
                }
                if (! $pedido->materiales_validados_at || ! $pedido->materiales_validados_por_id) {
                    PedidoService::error('materiales', 'Primero valida los materiales o confirma su validación al terminar.');
                }
                $this->comprobarPrecio($pedido);
                $pedido->precio_confirmado_por_id = $actor->id;
                $pedido->precio_confirmado_at = now();
                $pedido->estado = 'terminado';
            } else {
                if ($pedido->estado !== 'terminado' || ! $pedido->materiales_validados_at || ! $pedido->precio_confirmado_at
                    || ! $pedido->materiales_validados_por_id || ! $pedido->precio_confirmado_por_id) {
                    PedidoService::error('estado', 'Solo puedes entregar un pedido terminado y validado.');
                }
                $pedido->estado = 'entregado';
            }
            $pedido->version++;
            $pedido->save();
            $evento = new EstadoPedido;
            $evento->pedido_id = $pedido->id;
            $evento->estado_anterior = $anterior;
            $evento->estado_nuevo = $pedido->estado;
            $evento->usuario_id = $actor->id;
            $evento->ocurrido_at = now();
            $evento->motivo = $accion === 'entregar' ? ($datos['observaciones'] ?? null) : null;
            $evento->version_resultante = $pedido->version;
            $evento->clave_operacion = $clave;
            $evento->hash_solicitud = $hash;
            $evento->save();
            if ($accion === 'terminar') {
                app(RetencionCasoService::class)->retener($pedido, $actor);
            }
            app(AuditoriaService::class)->registrar($actor, $pedido, $accion === 'terminar' ? 'fabricacion_terminada' : 'pedido_entregado', $antes, app(AuditoriaService::class)->datos($pedido));

            return $pedido;
        }, 5);
    }
}
