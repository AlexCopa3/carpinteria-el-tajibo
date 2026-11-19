<?php

namespace App\Services;

use App\Http\Requests\Pedidos\ActualizarPedidoRequest;
use App\Http\Requests\Pedidos\CambiarEstadoPedidoRequest;
use App\Http\Requests\Pedidos\CrearPedidoRequest;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\DetallePedido;
use App\Models\EstadoPedido;
use App\Models\MaterialPedido;
use App\Models\Pedido;
use App\Models\User;
use App\Support\Acceso;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PedidoService
{
    public static function validar(array $datos, array $reglas): array
    {
        $permitidos = array_filter(array_keys($reglas), fn ($campo) => ! str_contains($campo, '.'));
        if (array_diff(array_keys($datos), $permitidos)) {
            throw ValidationException::withMessages(['solicitud' => 'La solicitud contiene campos no permitidos.']);
        }

        return Validator::make($datos, $reglas)->validate();
    }

    public static function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }

    public function crear(User $actor, array $datos): Pedido
    {
        Gate::forUser($actor->fresh())->authorize('create', Pedido::class);
        Gate::forUser($actor->fresh())->authorize('viewAny', Cliente::class);
        $datos = self::validar($datos, CrearPedidoRequest::reglas());
        $clave = strtolower($datos['clave_registro']);
        $hash = $this->huella($actor, $datos, 'clave_registro');

        return DB::transaction(function () use ($actor, $datos, $clave, $hash) {
            Gate::forUser($actor->fresh())->authorize('create', Pedido::class);
            $cotizacion = Cotizacion::whereKey($datos['cotizacion_id'])->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('view', $cotizacion);
            $anterior = Pedido::where('clave_registro', $clave)->lockForUpdate()->first();
            if ($anterior) {
                if ($anterior->registrado_por_id !== $actor->id || ! hash_equals($anterior->hash_solicitud, $hash)) {
                    self::error('clave_registro', 'La clave ya fue utilizada con otros datos.');
                }

                return $anterior;
            }
            $existente = Pedido::where('cotizacion_id', $cotizacion->id)->lockForUpdate()->first();
            if ($existente) {
                if ($existente->registrado_por_id !== $actor->id || ! hash_equals($existente->hash_solicitud, $hash)) {
                    self::error('cotizacion_id', 'Esta cotización ya tiene un pedido. Abre el pedido existente.');
                }

                return $existente;
            }
            if (! $cotizacion->aprobado_at || $cotizacion->precio_final === null || $cotizacion->estado !== 'aprobada') {
                self::error('cotizacion_id', 'Solo se aceptan cotizaciones aprobadas.');
            }
            $detalles = $cotizacion->detalles()->with('materiales')->get();
            if ($detalles->isEmpty() || $detalles->contains(fn ($detalle) => $detalle->precio_final === null || $detalle->costo_total === null)) {
                self::error('cotizacion_id', 'Revisa los detalles aprobados.');
            }
            $total = BigDecimal::zero();
            foreach ($detalles as $detalle) {
                $total = $total->plus($detalle->precio_final);
            }
            if (! $total->isEqualTo($cotizacion->precio_final)) {
                self::error('cotizacion_id', 'El total no coincide con sus detalles aprobados.');
            }
            if (array_key_exists('fecha_prevista_entrega', $datos) || array_key_exists('responsable_id', $datos)) {
                abort_unless(Acceso::permite($actor->fresh(), 'pedidos.programar'), 403);
            }
            $responsable = $this->responsable($datos['responsable_id'] ?? null);
            if (! empty($datos['cliente_id'])) {
                if (array_filter($datos['cliente'] ?? [], fn ($valor) => $valor !== null && $valor !== '')) {
                    self::error('cliente', 'Selecciona un cliente existente o registra uno nuevo, no ambos.');
                }
                $cliente = Cliente::whereKey($datos['cliente_id'])->lockForUpdate()->firstOrFail();
                Gate::forUser($actor->fresh())->authorize('view', $cliente);
                if (! $cliente->activo) {
                    self::error('cliente_id', 'Selecciona un cliente activo.');
                }
            } else {
                Gate::forUser($actor->fresh())->authorize('create', Cliente::class);
                $cliente = new Cliente;
                $cliente->nombre = $datos['cliente']['nombre'];
                $cliente->tipo = $datos['cliente']['tipo'];
                $cliente->telefono = $datos['cliente']['telefono'];
                $cliente->email = $datos['cliente']['email'] ?? null;
                $cliente->direccion = $datos['cliente']['direccion'] ?? null;
                $cliente->observaciones = $datos['cliente']['observaciones'] ?? null;
                $cliente->activo = true;
                $cliente->save();
                app(AuditoriaService::class)->registrar($actor, $cliente, 'creado', [], app(AuditoriaService::class)->datos($cliente));
            }
            $pedido = new Pedido;
            $pedido->cotizacion_id = $cotizacion->id;
            $pedido->cliente_id = $cliente->id;
            $pedido->datos_cliente_acordados = $cliente->only(['nombre', 'tipo', 'telefono', 'email', 'direccion']);
            $pedido->fecha_aceptacion = today();
            $pedido->fecha_prevista_entrega = $datos['fecha_prevista_entrega'] ?? null;
            $pedido->responsable_id = $responsable;
            $pedido->registrado_por_id = $actor->id;
            $pedido->estado = 'aceptado';
            $pedido->version = 1;
            $pedido->cotizacion_aceptada_version = $cotizacion->version;
            $pedido->observaciones = $datos['observaciones'] ?? null;
            $pedido->precio_total_acordado = (string) $total;
            $pedido->clave_registro = $clave;
            $pedido->hash_solicitud = $hash;
            $pedido->save();
            foreach ($detalles as $origen) {
                $detalle = new DetallePedido;
                $detalle->pedido_id = $pedido->id;
                $detalle->detalle_cotizacion_id = $origen->id;
                $detalle->posicion = $origen->posicion;
                $detalle->cantidad_muebles = $origen->cantidad_muebles;
                $detalle->precio_acordado = $origen->precio_final;
                $detalle->save();
                foreach ($origen->materiales as $material) {
                    (new MaterialPedido)->forceFill([
                        'detalle_pedido_id' => $detalle->id, 'material_cotizacion_id' => $material->id,
                        'material_id' => $material->material_id, 'unidad_id' => $material->unidad_id,
                        'material_nombre' => $material->material_nombre, 'unidad_nombre' => $material->unidad_nombre,
                        'componente' => $material->componente, 'cantidad_inicial' => $material->cantidad_total_aplicada,
                        'cantidad_planificada' => $material->cantidad_total_aplicada, 'actualizado_por_id' => $actor->id,
                    ])->save();
                }
            }
            $this->evento($actor, $pedido, null, $clave, $hash);
            app(AuditoriaService::class)->registrar($actor, $pedido, 'creado', [], app(AuditoriaService::class)->datos($pedido));

            return $pedido;
        }, 5);
    }

    public function actualizar(User $actor, Pedido $pedido, array $datos): Pedido
    {
        $datos = self::validar($datos, ActualizarPedidoRequest::reglas());

        return DB::transaction(function () use ($actor, $pedido, $datos) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $pedido);
            $this->version($pedido, $datos['version']);
            if (array_key_exists('fecha_prevista_entrega', $datos) || array_key_exists('responsable_id', $datos)) {
                Gate::forUser($actor->fresh())->authorize('programar', $pedido);
            }
            $antes = app(AuditoriaService::class)->datos($pedido);
            if (array_key_exists('fecha_prevista_entrega', $datos)) {
                if ($datos['fecha_prevista_entrega'] && $datos['fecha_prevista_entrega'] < $pedido->fecha_aceptacion->format('Y-m-d')) {
                    self::error('fecha_prevista_entrega', 'La entrega no puede ser anterior a la aceptación.');
                }
                $pedido->fecha_prevista_entrega = $datos['fecha_prevista_entrega'];
            }
            if (array_key_exists('responsable_id', $datos)) {
                $pedido->responsable_id = $this->responsable($datos['responsable_id']);
            }
            if (array_key_exists('observaciones', $datos)) {
                $pedido->observaciones = $datos['observaciones'];
            }
            $pedido->version++;
            $pedido->save();
            app(AuditoriaService::class)->registrar($actor, $pedido, 'modificado', $antes, app(AuditoriaService::class)->datos($pedido));

            return $pedido;
        }, 5);
    }

    public function cambiarEstado(User $actor, Pedido $pedido, array $datos): Pedido
    {
        $datos = self::validar($datos, CambiarEstadoPedidoRequest::reglas());
        $clave = strtolower($datos['clave_operacion']);
        $hash = $this->huella($actor, ['pedido_id' => $pedido->id] + $datos, 'clave_operacion');

        return DB::transaction(function () use ($actor, $pedido, $datos, $clave, $hash) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $pedido);
            $evento = EstadoPedido::where('clave_operacion', $clave)->lockForUpdate()->first();
            if ($evento) {
                if ($evento->pedido_id !== $pedido->id || $evento->usuario_id !== $actor->id || ! hash_equals($evento->hash_solicitud, $hash)) {
                    self::error('clave_operacion', 'La clave ya fue utilizada con otros datos.');
                }

                return $pedido;
            }
            $this->version($pedido, $datos['version']);
            if ($pedido->estado !== 'aceptado') {
                self::error('estado', 'Solo puedes iniciar un pedido aceptado.');
            }
            $antes = app(AuditoriaService::class)->datos($pedido);
            $pedido->estado = 'en_fabricacion';
            $pedido->version++;
            $pedido->save();
            $this->evento($actor, $pedido, 'aceptado', $clave, $hash);
            app(AuditoriaService::class)->registrar($actor, $pedido, 'modificado', $antes, app(AuditoriaService::class)->datos($pedido));

            return $pedido;
        }, 5);
    }

    public function aceptarCotizacion(User $actor, Pedido $pedido, array $datos): Pedido
    {
        $datos = self::validar($datos, ['version' => 'required|integer|min:1', 'cotizacion_version' => 'required|integer|min:1', 'aceptacion' => 'required|accepted']);

        return DB::transaction(function () use ($actor, $pedido, $datos) {
            $pedido = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('aceptarCotizacion', $pedido);
            $cotizacion = $pedido->cotizacion()->lockForUpdate()->firstOrFail();
            if ($cotizacion->version !== (int) $datos['cotizacion_version'] || ! $cotizacion->aprobado_at || $cotizacion->estado !== 'aprobada') {
                self::error('cotizacion', 'Revisa y aprueba la cotización actual antes de confirmar.');
            }
            if ($pedido->cotizacion_aceptada_version === $cotizacion->version) {
                return $pedido;
            }
            $this->version($pedido, $datos['version']);
            $cotizados = $cotizacion->detalles()->with('materiales')->lockForUpdate()->get();
            $actuales = $pedido->detalles()->with('materiales')->lockForUpdate()->get()->keyBy('detalle_cotizacion_id');
            if ($actuales->keys()->diff($cotizados->pluck('id'))->isNotEmpty()) {
                self::error('detalles', 'Conserva los muebles que ya pertenecen al pedido.');
            }
            $total = BigDecimal::zero();
            foreach ($cotizados as $cotizado) {
                if ($cotizado->precio_final === null || ! BigDecimal::of($cotizado->precio_final)->isPositive()) {
                    self::error('precios', 'Todos los muebles necesitan un precio aprobado positivo.');
                }
                $total = $total->plus($cotizado->precio_final);
                $detalle = $actuales->get($cotizado->id);
                if ($detalle && $cotizado->cantidad_muebles < $detalle->cantidad_muebles) {
                    self::error('detalles', 'La cantidad de muebles aceptados no puede reducirse desde este formulario.');
                }
                foreach ($detalle?->materiales ?? [] as $fila) {
                    $material = $cotizado->materiales->firstWhere('id', $fila->material_cotizacion_id);
                    $cantidad = $material?->cantidad_total_aplicada ?? '0';
                    if ($fila->cantidad_utilizada !== null && BigDecimal::of($cantidad)->isLessThan($fila->cantidad_utilizada)) {
                        self::error('materiales', 'La cotización debe incluir al menos las cantidades utilizadas.');
                    }
                }
            }
            if ($cotizados->isEmpty() || $cotizacion->precio_final === null || ! $total->isEqualTo($cotizacion->precio_final)) {
                self::error('precios', 'El total no coincide con los precios aprobados.');
            }
            $pagado = BigDecimal::zero();
            foreach ($pedido->pagos()->orderBy('id')->lockForUpdate()->get() as $pago) {
                $pagado = $pagado->plus($pago->importe);
            }
            if ($total->isLessThan($pagado)) {
                self::error('precios', 'El nuevo precio no puede ser menor que los pagos registrados.');
            }
            $antes = $pedido->only(['precio_total_acordado', 'cotizacion_aceptada_version', 'version']);
            foreach ($actuales as $detalle) {
                $detalle->posicion += 1000;
                $detalle->save();
            }
            foreach ($cotizados as $cotizado) {
                $detalle = $actuales->get($cotizado->id) ?? new DetallePedido;
                $detalle->pedido_id = $pedido->id;
                $detalle->detalle_cotizacion_id = $cotizado->id;
                $detalle->cantidad_muebles = $cotizado->cantidad_muebles;
                $detalle->precio_acordado = $cotizado->precio_final;
                $detalle->posicion = $cotizado->posicion;
                $detalle->save();
                foreach ($cotizado->materiales as $material) {
                    $fila = $detalle->materiales()->where('material_cotizacion_id', $material->id)->first();
                    $fila ??= $detalle->materiales()->where('material_id', $material->material_id)->where('unidad_id', $material->unidad_id)->where('componente', $material->componente)->whereNull('material_cotizacion_id')->first();
                    $fila ??= new MaterialPedido;
                    $fila->detalle_pedido_id = $detalle->id;
                    $fila->material_cotizacion_id = $material->id;
                    foreach (['material_id', 'unidad_id', 'material_nombre', 'unidad_nombre', 'componente'] as $campo) {
                        $fila->{$campo} = $material->{$campo};
                    }
                    $fila->cantidad_planificada = $material->cantidad_total_aplicada;
                    $fila->actualizado_por_id = $actor->id;
                    $fila->save();
                }
                $detalle->materiales()->whereNotIn('material_cotizacion_id', $cotizado->materiales->pluck('id'))->update(['cantidad_planificada' => '0']);
                $detalle->materiales()->whereNull('material_cotizacion_id')->update(['cantidad_planificada' => '0']);
            }
            $pedido->precio_total_acordado = (string) $total->toScale(2);
            $pedido->cotizacion_aceptada_version = $cotizacion->version;
            $pedido->version++;
            app(CierrePedidoService::class)->invalidarMateriales($actor, $pedido);
            $pedido->precio_confirmado_por_id = null;
            $pedido->precio_confirmado_at = null;
            $pedido->save();
            app(AuditoriaService::class)->registrar($actor, $pedido, 'cotizacion_aceptada', $antes, $pedido->only(array_keys($antes)));

            return $pedido;
        }, 5);
    }

    public function version(Pedido $pedido, mixed $version): void
    {
        if ((int) $version !== $pedido->version) {
            self::error('version', 'El pedido cambió. Recarga la página antes de guardar.');
        }
    }

    private function responsable(mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $usuario = User::whereKey($id)->firstOrFail();
        if (! $usuario->activo || ! $usuario->hasAnyRole(['propietario', 'maestro'])) {
            self::error('responsable_id', 'Selecciona una persona activa de la carpintería.');
        }

        return $usuario->id;
    }

    private function huella(User $actor, array $datos, string $clave): string
    {
        unset($datos[$clave]);
        $ordenar = function (array $valores) use (&$ordenar): array {
            ksort($valores);
            foreach ($valores as &$valor) {
                if (is_array($valor)) {
                    $valor = $ordenar($valor);
                }
            }

            return $valores;
        };

        return hash('sha256', json_encode([$actor->id, $ordenar($datos)], JSON_THROW_ON_ERROR));
    }

    private function evento(User $actor, Pedido $pedido, ?string $anterior, string $clave, string $hash): void
    {
        (new EstadoPedido)->forceFill(['pedido_id' => $pedido->id, 'estado_anterior' => $anterior, 'estado_nuevo' => $pedido->estado, 'usuario_id' => $actor->id, 'ocurrido_at' => now(), 'version_resultante' => $pedido->version, 'clave_operacion' => $clave, 'hash_solicitud' => $hash])->save();
    }
}
