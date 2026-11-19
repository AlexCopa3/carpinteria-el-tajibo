<?php

namespace App\Services;

use App\Http\Requests\Cotizaciones\GuardarCotizacionRequest;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\Material;
use App\Models\MaterialCotizacion;
use App\Models\Pedido;
use App\Models\TipoMueble;
use App\Models\User;
use App\Support\Acceso;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CotizacionService
{
    public function guardar(User $actor, array $datos, ?Cotizacion $cotizacion = null): Cotizacion
    {
        $actor = $actor->fresh();
        Gate::forUser($actor)->authorize($cotizacion ? 'update' : 'create', $cotizacion ?? Cotizacion::class);
        abort_unless(Acceso::permite($actor, 'costos.consultar'), 403);
        $datos = Validator::make($datos, GuardarCotizacionRequest::reglas($actor, $cotizacion !== null, $cotizacion, $datos['detalles'] ?? []))->validate();
        $hash = hash('sha256', json_encode(['actor' => $actor->id, 'datos' => $datos], JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($actor, $datos, $cotizacion, $hash) {
                $actor = $actor->fresh();
                $edicion = $cotizacion !== null;
                if ($edicion) {
                    Pedido::where('cotizacion_id', $cotizacion->id)->lockForUpdate()->first();
                }
                $cotizacion = $edicion
                    ? Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail()
                    : new Cotizacion;
                Gate::forUser($actor)->authorize($edicion ? 'update' : 'create', $edicion ? $cotizacion : Cotizacion::class);
                abort_unless(Acceso::permite($actor, 'costos.consultar'), 403);
                if ($edicion && (int) $datos['version'] !== $cotizacion->version) {
                    $this->error('version', 'La cotización cambió. Recarga la ficha antes de editar.');
                }
                if (! $edicion) {
                    $anterior = Cotizacion::where('clave_registro', strtolower($datos['clave_registro']))->first();
                    if ($anterior) {
                        return $this->repetida($anterior, $actor, $hash);
                    }
                    $cotizacion->clave_registro = strtolower($datos['clave_registro']);
                    $cotizacion->solicitud_hash = $hash;
                    $cotizacion->responsable_id = $actor->id;
                    $cotizacion->fecha = today();
                    $cotizacion->version = 1;
                    $cotizacion->estado = 'borrador';
                    $cotizacion->recargo_porcentaje = null;
                }
                $antes = $edicion ? $this->instantanea($cotizacion) : [];
                if ($edicion && $cotizacion->aprobado_at) {
                    DB::table('cotizaciones')->where('id', $cotizacion->id)->update([
                        'estado' => 'borrador', 'precio_final' => null, 'aprobado_at' => null,
                        'aprobado_por_id' => null, 'validado_at' => null, 'validado_por_id' => null,
                    ]);
                    $cotizacion->refresh();
                    $cotizacion->detalles()->update(['precio_final' => null]);
                }
                // Conservar las notas anteriores cuando el formulario ya no las envía.
                if (array_key_exists('observaciones', $datos)) {
                    $cotizacion->observaciones = $datos['observaciones'];
                }
                // No borrar condiciones anteriores al guardar desde el formulario simplificado.
                if (array_key_exists('condiciones', $datos)) {
                    $cotizacion->condiciones = $datos['condiciones'];
                }
                if (array_key_exists('recargo_porcentaje', $datos)) {
                    abort_unless(Acceso::permite($actor, 'margenes.gestionar'), 403);
                    $cotizacion->recargo_porcentaje = $this->porcentaje($datos['recargo_porcentaje']);
                }
                if ($edicion) {
                    $cotizacion->version++;
                }
                $cotizacion->save();
                $detallesPrevios = $cotizacion->detalles()->get()->keyBy('id');
                foreach ($detallesPrevios as $previo) {
                    $previo->posicion += 1000;
                    $previo->save();
                }
                $retiros = $this->retirarMateriales($actor, $cotizacion, $datos['detalles']);
                $conservados = [];
                foreach (array_values($datos['detalles']) as $posicion => $datosDetalle) {
                    $detalle = isset($datosDetalle['id']) ? $detallesPrevios->get($datosDetalle['id']) : new DetalleCotizacion;
                    if (! $detalle) {
                        $this->error('detalles', 'Un mueble no pertenece a esta cotización.');
                    }
                    $referencia = ! empty($datosDetalle['referencia_caso'])
                        ? app(PropuestaCasoService::class)->verificarReferencia($actor, $datosDetalle['referencia_caso']) : null;

                    $this->guardarDetalle($actor, $cotizacion, $detalle, $datosDetalle, $posicion + 1);
                    if ($referencia) {
                        app(AuditoriaService::class)->registrar($actor, $detalle, 'referencia_caso_utilizada', [], [
                            'referencia' => $referencia,
                            'aclaracion' => 'Propuesta editable; los importes guardados se validan y calculan en servidor, sin aprobación automática.',
                        ]);
                    }
                    $conservados[] = $detalle->id;
                }
                foreach ($detallesPrevios as $detalle) {
                    if (! in_array($detalle->id, $conservados, true)) {
                        if ($detalle->detallePedido()->exists()) {
                            $this->error('detalles', 'No elimines un mueble que ya pertenece a un pedido.');
                        }
                        $detalle->materiales()->delete();
                        $detalle->delete();
                    }
                }
                $cotizacion->unsetRelation('detalles');
                $this->sumar($cotizacion);
                $cotizacion->save();
                app(AuditoriaService::class)->registrar($actor, $cotizacion, $edicion ? 'modificado' : 'creado', $antes, $this->instantanea($cotizacion));

                $resultado = $cotizacion->fresh(['detalles.materiales', 'cliente', 'responsable']);
                $pedido = $resultado->pedido;
                if ($pedido) {
                    app(CierrePedidoService::class)->invalidarMateriales($actor, $pedido);
                    if ($retiros) {
                        $pedido->version++;
                    }
                    $pedido->precio_confirmado_por_id = null;
                    $pedido->precio_confirmado_at = null;
                    $pedido->save();
                }

                return $resultado;
            }, 5);
        } catch (QueryException $error) {
            if ($cotizacion === null && isset($datos['clave_registro'])) {
                $anterior = Cotizacion::where('clave_registro', strtolower($datos['clave_registro']))->first();
                if ($anterior) {
                    return $this->repetida($anterior, $actor, $hash);
                }
            }
            throw $error;
        }
    }

    public function sincronizarUso(User $actor, Pedido $pedido): ?Cotizacion
    {
        $cotizacion = Cotizacion::whereKey($pedido->cotizacion_id)->lockForUpdate()->firstOrFail();
        $detallesPedido = $pedido->detalles()->with('materiales.material')->get()->keyBy('detalle_cotizacion_id');
        $datos = [];
        $cambios = false;
        foreach ($cotizacion->detalles()->with('materiales')->get() as $detalle) {
            $entrada = $detalle->only(['id', 'tipo_mueble_id', 'material_principal_id', 'largo_cm', 'ancho_cm', 'alto_cm', 'puertas', 'cajones', 'divisiones', 'acabado', 'cantidad_muebles', 'observaciones', 'mano_obra_unitaria', 'mano_obra_total_aplicada', 'motivo_ajuste_mano_obra', 'otros_costos_total', 'precio_propuesto']);
            $entrada['materiales'] = $detalle->materiales->map(fn ($fila) => $fila->only(['id', 'material_id', 'componente', 'cantidad_unitaria', 'cantidad_total_aplicada', 'costo_unitario_estimado', 'estado_costo', 'fuente_costo', 'motivo_ajuste_cantidad']))->all();
            foreach ($detallesPedido->get($detalle->id)?->materiales ?? [] as $usado) {
                if ($usado->cantidad_utilizada === null) {
                    continue;
                }
                $indice = null;
                foreach ($entrada['materiales'] as $posicion => $fila) {
                    if (($fila['id'] ?? null) === $usado->material_cotizacion_id) {
                        $indice = $posicion;
                        break;
                    }
                }
                if ($indice === null) {
                    if (BigDecimal::of($usado->cantidad_utilizada)->isZero()) {
                        continue;
                    }
                    $precio = $usado->material->precio_referencia;
                    $conocido = $precio !== null && BigDecimal::of($precio)->isPositive();
                    $indice = count($entrada['materiales']);
                    $entrada['materiales'][] = ['material_id' => $usado->material_id, 'componente' => $usado->componente,
                        'cantidad_unitaria' => '0', 'cantidad_total_aplicada' => '0', 'estado_costo' => $conocido ? 'conocido' : 'pendiente',
                        'fuente_costo' => 'referencia_catalogo', 'costo_unitario_estimado' => $conocido ? $precio : null];
                    $cambios = true;
                }
                $fila = &$entrada['materiales'][$indice];
                if (! BigDecimal::of($fila['cantidad_total_aplicada'])->isEqualTo($usado->cantidad_utilizada)) {
                    $fila['cantidad_total_aplicada'] = $usado->cantidad_utilizada;
                    $fila['cantidad_unitaria'] = (string) BigDecimal::of($usado->cantidad_utilizada)->dividedBy($detalle->cantidad_muebles, 4, RoundingMode::HALF_UP);
                    $fila['motivo_ajuste_cantidad'] = 'Cantidad total registrada durante fabricación';
                    $cambios = true;
                }
                unset($fila);
            }
            $datos[] = $entrada;
        }
        if (! $cambios) {
            return null;
        }
        if (! Gate::forUser($actor->fresh())->allows('editarCotizacion', $pedido)) {
            $pedido->setRelation('avisoCotizacion', (object) ['mensaje' => 'Uso guardado. Una persona autorizada debe actualizar los costos de la cotización.']);

            return null;
        }
        $cotizacion = $this->guardar($actor, ['version' => $cotizacion->version, 'observaciones' => $cotizacion->observaciones, 'detalles' => $datos], $cotizacion);
        foreach ($detallesPedido as $detallePedido) {
            $detalle = $cotizacion->detalles->firstWhere('id', $detallePedido->detalle_cotizacion_id);
            foreach ($detallePedido->materiales as $usado) {
                if ($usado->material_cotizacion_id || ! $detalle) {
                    continue;
                }
                $fila = $detalle->materiales->first(fn ($fila) => $fila->material_id === $usado->material_id && $fila->unidad_id === $usado->unidad_id && $fila->componente === $usado->componente);
                if ($fila) {
                    $usado->material_cotizacion_id = $fila->id;
                    $usado->save();
                }
            }
        }

        return $cotizacion;
    }

    private function retirarMateriales(User $actor, Cotizacion $cotizacion, array &$detalles): bool
    {
        $modificado = false;
        foreach ($detalles as $indiceDetalle => &$detalle) {
            foreach ($detalle['materiales'] as $indiceMaterial => &$material) {
                if (empty($material['retirar'])) {
                    continue;
                }
                $campo = 'detalles.'.$indiceDetalle.'.materiales.'.$indiceMaterial;
                $detalleCotizado = $cotizacion->detalles()->whereKey($detalle['id'] ?? null)->first();
                $cotizado = $detalleCotizado?->materiales()->whereKey($material['id'] ?? null)->first();
                if (! $cotizado || ! $cotizado->materialesPedido()->exists()) {
                    $this->error($campo.'.retirar', 'El material no pertenece a un pedido de esta cotización.');
                }
                foreach ($cotizado->materialesPedido()->with('detalle.pedido')->lockForUpdate()->get() as $utilizado) {
                    $pedido = $utilizado->detalle->pedido;
                    if ($pedido->cotizacion_id !== $cotizacion->id) {
                        $this->error($campo.'.retirar', 'La referencia del material no corresponde al pedido.');
                    }
                    if ($pedido->estado === 'en_fabricacion') {
                        Gate::forUser($actor)->authorize('registrarUsoMateriales', $pedido);
                    }
                    $motivo = trim((string) ($material['motivo_retiro'] ?? ''));
                    if ($utilizado->cantidad_utilizada !== null && BigDecimal::of($utilizado->cantidad_utilizada)->isPositive()) {
                        Gate::forUser($actor)->authorize('registrarUsoMateriales', $pedido);
                        $motivo = $this->motivo($motivo, $campo.'.motivo_retiro');
                    }
                    if ($utilizado->cantidad_utilizada === null || ! BigDecimal::of($utilizado->cantidad_utilizada)->isZero()) {
                        $antes = $utilizado->getAttributes();
                        $utilizado->cantidad_utilizada = '0';
                        $utilizado->uso_registrado_por_id = $actor->id;
                        $utilizado->uso_registrado_at = now();
                        $utilizado->save();
                        app(AuditoriaService::class)->registrar($actor, $pedido, 'material_retirado_desde_cotizacion',
                            ['material' => $antes], ['material' => $utilizado->getAttributes(), 'motivo' => $motivo === '' ? null : $motivo]);
                        $modificado = true;
                    }
                }
                $material['cantidad_unitaria'] = '0';
                $material['cantidad_total_aplicada'] = '0';
                $material['motivo_ajuste_cantidad'] = null;
            }
            unset($material);
        }
        unset($detalle);

        return $modificado;
    }

    public function rechazar(User $actor, Cotizacion $cotizacion, array $datos): Cotizacion
    {
        $datos = Validator::make($datos, ['version' => 'required|integer|min:1', 'motivo' => 'required|string|min:5|max:2000', 'confirmacion' => 'required|accepted'])->validate();

        return DB::transaction(function () use ($actor, $cotizacion, $datos) {
            $cotizacion = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            $actor = $actor->fresh();
            Gate::forUser($actor)->authorize('reject', $cotizacion);
            if ($cotizacion->version !== (int) $datos['version']) {
                $this->error('version', 'La cotización cambió. Recarga antes de registrar el rechazo.');
            }
            $motivo = $this->motivo($datos['motivo'], 'motivo');
            $antes = $this->instantanea($cotizacion);
            $cotizacion->estado = 'rechazada';
            $cotizacion->version++;
            $cotizacion->save();
            app(AuditoriaService::class)->registrar($actor, $cotizacion, 'rechazada', $antes, ['estado' => $cotizacion->estado, 'version' => $cotizacion->version, 'motivo' => $motivo]);

            return $cotizacion->fresh();
        }, 5);
    }

    private function repetida(Cotizacion $cotizacion, User $actor, string $hash): Cotizacion
    {
        if ($cotizacion->responsable_id !== $actor->id || ! hash_equals($cotizacion->solicitud_hash, $hash)) {
            $this->error('clave_registro', 'La solicitud ya se utilizó con otros datos.');
        }

        return $cotizacion;
    }

    public function porcentaje(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $porcentaje = Decimal::numero($valor, 4, 'recargo_porcentaje');
        if ($porcentaje->isGreaterThan('99999.9999')) {
            $this->error('recargo_porcentaje', 'La ganancia sobre el costo está fuera del intervalo permitido.');
        }

        return (string) $porcentaje;
    }

    private function dinero(mixed $valor, string $campo): string
    {
        return (string) Decimal::numero($valor, 2, $campo);
    }

    private function importe(BigDecimal $valor, string $campo): string
    {
        return $this->dinero((string) $valor->toScale(2, RoundingMode::HALF_UP), $campo);
    }

    private function motivo(mixed $valor, string $campo): string
    {
        $motivo = trim((string) $valor);
        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 2000) {
            $this->error($campo, 'Explica el motivo con al menos cinco caracteres.');
        }

        return $motivo;
    }

    private function guardarDetalle(User $actor, Cotizacion $cotizacion, DetalleCotizacion $detalle, array $datos, int $posicion): void
    {
        $campo = 'detalles.'.($posicion - 1);
        $tipo = TipoMueble::findOrFail($datos['tipo_mueble_id']);
        $principal = Material::whereKey($datos['material_principal_id'])->lockForUpdate()->firstOrFail();
        $detalleConservado = $detalle->exists && $detalle->detallePedido()->exists();
        if ($detalleConservado) {
            foreach (['tipo_mueble_id', 'material_principal_id', 'puertas', 'cajones', 'divisiones', 'acabado'] as $campoProtegido) {
                if ((string) $datos[$campoProtegido] !== (string) $detalle->{$campoProtegido}) {
                    $this->error($campo, 'Conserva el diseño del mueble aceptado; para otro diseño añade un mueble.');
                }
            }
            foreach (['largo_cm', 'ancho_cm', 'alto_cm'] as $dimension) {
                if (! Decimal::numero($datos[$dimension], 4, $campo.'.'.$dimension, true)->isEqualTo($detalle->{$dimension})) {
                    $this->error($campo, 'Conserva las dimensiones del mueble aceptado.');
                }
            }
            if ((int) $datos['cantidad_muebles'] < $detalle->detallePedido->cantidad_muebles) {
                $this->error($campo, 'La cantidad de muebles aceptados no puede reducirse.');
            }
        }
        $conservaTipo = $detalleConservado && (int) $detalle->tipo_mueble_id === (int) $tipo->id;
        $conservaPrincipal = $detalleConservado && (int) $detalle->material_principal_id === (int) $principal->id;
        if ((! $tipo->activo && ! $conservaTipo) || (! $principal->activo && ! $conservaPrincipal)) {
            $this->error($campo, 'El tipo y el material principal deben estar activos.');
        }
        $conservaAnterior = $detalle->exists && (int) $detalle->material_principal_id === (int) $principal->id;
        if (! $conservaAnterior && ! Material::principales()->whereKey($principal->id)->exists()) {
            $this->error($campo.'.material_principal_id', 'Selecciona un material de la categoría Material principal.');
        }
        $detalle->cotizacion_id = $cotizacion->id;
        $detalle->posicion = $posicion;
        $detalle->tipo_mueble_id = $tipo->id;
        $detalle->tipo_nombre = $tipo->nombre;
        $detalle->material_principal_id = $principal->id;
        $detalle->material_principal_nombre = $principal->nombre;
        foreach (['largo_cm', 'ancho_cm', 'alto_cm'] as $dimension) {
            $valor = Decimal::numero($datos[$dimension], 4, $campo.'.'.$dimension, true);
            if ($valor->isGreaterThan('9999999999.9999')) {
                $this->error($campo.'.'.$dimension, 'La dimensión supera la precisión admitida.');
            }
            $detalle->$dimension = (string) $valor;
        }
        $detalle->puertas = $datos['puertas'];
        $detalle->cajones = $datos['cajones'];
        $detalle->divisiones = $datos['divisiones'];
        $detalle->acabado = trim($datos['acabado']);
        $detalle->cantidad_muebles = $datos['cantidad_muebles'];
        $detalle->observaciones = $datos['observaciones'] ?? null;
        $detalle->mano_obra_unitaria = $this->dinero($datos['mano_obra_unitaria'], $campo.'.mano_obra_unitaria');
        $nominal = BigDecimal::of($detalle->mano_obra_unitaria)->multipliedBy($detalle->cantidad_muebles);
        $detalle->mano_obra_total_nominal = $this->importe($nominal, $campo.'.mano_obra_unitaria');
        $aplicada = isset($datos['mano_obra_total_aplicada'])
            ? $this->dinero($datos['mano_obra_total_aplicada'], $campo.'.mano_obra_total_aplicada') : $detalle->mano_obra_total_nominal;
        $ajuste = ! BigDecimal::of($aplicada)->isEqualTo($nominal);
        $detalle->mano_obra_total_aplicada = $aplicada;
        $detalle->motivo_ajuste_mano_obra = $ajuste ? $this->motivo($datos['motivo_ajuste_mano_obra'] ?? null, $campo.'.motivo_ajuste_mano_obra') : null;
        $detalle->ajustado_por_id = $ajuste ? $actor->id : null;
        $detalle->ajustado_at = $ajuste ? now() : null;
        $detalle->otros_costos_total = $this->dinero($datos['otros_costos_total'], $campo.'.otros_costos_total');
        $detalle->precio_propuesto = isset($datos['precio_propuesto']) ? $this->dinero($datos['precio_propuesto'], $campo.'.precio_propuesto') : null;
        $detalle->save();
        $previos = $detalle->materiales()->get()->keyBy('id');
        foreach ($previos as $previo) {
            $previo->posicion += 1000;
            $previo->save();
        }
        $conservados = [];
        $suma = BigDecimal::zero();
        $pendiente = false;
        $incluyePrincipal = false;
        foreach (array_values($datos['materiales']) as $indice => $entrada) {
            $materialCotizado = isset($entrada['id']) ? $previos->get($entrada['id']) : new MaterialCotizacion;
            if (! $materialCotizado) {
                $this->error($campo.'.materiales', 'Un material no pertenece a este mueble.');
            }
            $this->guardarMaterial($actor, $detalle, $materialCotizado, $entrada, $indice + 1, $campo.'.materiales.'.$indice);
            $conservados[] = $materialCotizado->id;
            $incluyePrincipal = $incluyePrincipal || $materialCotizado->material_id === $principal->id;
            if ($materialCotizado->subtotal_aplicado === null) {
                $pendiente = true;
            } else {
                $suma = $suma->plus($materialCotizado->subtotal_aplicado);
            }
        }
        if (! $incluyePrincipal) {
            $this->error($campo.'.materiales', 'Incluye el material principal entre los materiales del mueble.');
        }
        foreach ($previos as $previo) {
            if (! in_array($previo->id, $conservados, true)) {
                if ($previo->materialesPedido()->where('cantidad_utilizada', '>', 0)->exists()) {
                    $this->error('materiales', 'Este material tiene consumo registrado. Corrige su uso antes de quitarlo.');
                }
                $previo->materialesPedido()->update(['material_cotizacion_id' => null]);
                $previo->delete();
            }
        }
        $detalle->costo_materiales_total = $pendiente ? null : $this->importe($suma, $campo.'.materiales');
        $detalle->costo_total = $pendiente ? null : $this->importe($suma->plus($detalle->mano_obra_total_aplicada)->plus($detalle->otros_costos_total), $campo);
        $detalle->precio_sugerido = $detalle->costo_total === null || $cotizacion->recargo_porcentaje === null
            ? null : $this->importe(BigDecimal::of($detalle->costo_total)->multipliedBy(BigDecimal::of($cotizacion->recargo_porcentaje)->dividedBy('100', 6)->plus(1)), $campo);
        $detalle->save();
    }

    private function guardarMaterial(User $actor, DetalleCotizacion $detalle, MaterialCotizacion $fila, array $datos, int $posicion, string $campo): void
    {
        $material = Material::with('unidad')->whereKey($datos['material_id'])->lockForUpdate()->firstOrFail();
        $materialConservado = $fila->exists && $fila->material_id === $material->id && $fila->unidad_id === $material->unidad_id
            && $fila->materialesPedido()->exists();
        if (! $material->activo && ! $materialConservado) {
            $this->error($campo.'.material_id', 'Selecciona un material activo.');
        }
        if ($fila->exists && ($fila->material_id !== $material->id || $fila->unidad_id !== $material->unidad_id) && $fila->materialesPedido()->exists()) {
            $this->error($campo.'.material_id', 'Conserva la referencia del pedido; quita esta fila y añade otra para un material diferente.');
        }
        $mismaReferencia = $fila->exists && $fila->material_id === $material->id;
        $permiteCero = $mismaReferencia && $fila->materialesPedido()->exists();
        $fila->detalle_cotizacion_id = $detalle->id;
        $fila->posicion = $posicion;
        $fila->material_id = $material->id;
        $fila->unidad_id = $material->unidad_id;
        $fila->material_nombre = $material->nombre;
        $fila->unidad_nombre = $material->unidad->nombre;
        $fila->componente = trim($datos['componente']);
        $fila->cantidad_unitaria = (string) Decimal::numero($datos['cantidad_unitaria'], 4, $campo.'.cantidad_unitaria', ! $permiteCero);
        $nominal = BigDecimal::of($fila->cantidad_unitaria)->multipliedBy($detalle->cantidad_muebles);
        $fila->cantidad_total_nominal = (string) Decimal::numero((string) $nominal, 4, $campo.'.cantidad_unitaria', ! $permiteCero);
        $aplicada = isset($datos['cantidad_total_aplicada']) ? $datos['cantidad_total_aplicada'] : $fila->cantidad_total_nominal;
        $fila->cantidad_total_aplicada = (string) Decimal::cantidad($aplicada, $material->unidad, $campo.'.cantidad_total_aplicada', ! $permiteCero);
        $ajuste = ! BigDecimal::of($fila->cantidad_total_aplicada)->isEqualTo($nominal);
        $fila->motivo_ajuste_cantidad = $ajuste ? $this->motivo($datos['motivo_ajuste_cantidad'] ?? null, $campo.'.motivo_ajuste_cantidad') : null;
        $fila->ajustado_por_id = $ajuste ? $actor->id : null;
        $fila->ajustado_at = $ajuste ? now() : null;
        if (! $mismaReferencia) {
            $fila->precio_referencia_capturado = $material->precio_referencia;
        }
        $fila->estado_costo = $datos['estado_costo'];
        $fila->fuente_costo = $datos['fuente_costo'];
        if ($datos['estado_costo'] === 'pendiente') {
            if (isset($datos['costo_unitario_estimado'])) {
                $this->error($campo.'.costo_unitario_estimado', 'El costo desconocido debe quedar vacío; no es cero.');
            }
            $fila->costo_unitario_estimado = null;
            $fila->subtotal_nominal = $permiteCero && $nominal->isZero() ? '0.00' : null;
            $fila->subtotal_aplicado = $permiteCero && BigDecimal::of($fila->cantidad_total_aplicada)->isZero() ? '0.00' : null;
        } else {
            $costo = Decimal::numero($datos['costo_unitario_estimado'] ?? null, 6, $campo.'.costo_unitario_estimado', true);
            $fila->costo_unitario_estimado = (string) $costo;
            $fila->subtotal_nominal = $this->importe($nominal->multipliedBy($costo), $campo.'.costo_unitario_estimado');
            $fila->subtotal_aplicado = $this->importe(BigDecimal::of($fila->cantidad_total_aplicada)->multipliedBy($costo), $campo.'.costo_unitario_estimado');
            if ($datos['estado_costo'] === 'conocido' && BigDecimal::of($fila->subtotal_aplicado)->isZero() && ! ($permiteCero && BigDecimal::of($fila->cantidad_total_aplicada)->isZero())) {
                $this->error($campo.'.costo_unitario_estimado', 'El costo conocido no puede redondearse a cero.');
            }
        }
        $fila->save();
    }

    private function sumar(Cotizacion $cotizacion): void
    {
        $detalles = $cotizacion->detalles()->get();
        $pendiente = $detalles->contains(fn ($detalle) => $detalle->costo_total === null);
        foreach (['costo_materiales' => 'costo_materiales_total', 'costo_mano_obra' => 'mano_obra_total_aplicada', 'otros_costos' => 'otros_costos_total', 'costo_total' => 'costo_total', 'precio_sugerido' => 'precio_sugerido'] as $campo => $origen) {
            $suma = BigDecimal::zero();
            $nulo = false;
            foreach ($detalles as $detalle) {
                if ($detalle->$origen === null) {
                    $nulo = true;
                } else {
                    $suma = $suma->plus($detalle->$origen);
                }
            }
            $cotizacion->$campo = $nulo ? null : $this->importe($suma, $campo);
        }
        $cotizacion->estado = $pendiente ? 'pendiente_costos' : 'borrador';
    }

    public function aprobar(User $actor, Cotizacion $cotizacion, array $datos): Cotizacion
    {
        return DB::transaction(function () use ($actor, $cotizacion, $datos) {
            $cotizacion = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            $actor = $actor->fresh();
            Gate::forUser($actor)->authorize('approve', $cotizacion);
            Gate::forUser($actor)->authorize('validatePrice', $cotizacion);
            abort_unless(Acceso::permite($actor, 'costos.consultar'), 403);
            Validator::make($datos, ['version' => 'required|integer|min:1', 'precios' => 'required|array|min:1|max:20', 'precios.*' => ['required', 'regex:/^\d{1,16}(?:\.\d{1,2})?$/D']])->validate();
            if ((int) $datos['version'] !== $cotizacion->version) {
                $this->error('version', 'El presupuesto cambió. Recarga y revisa la versión actual.');
            }
            $detalles = $cotizacion->detalles()->lockForUpdate()->get();
            $esperados = $detalles->pluck('id')->map(fn ($id) => (string) $id)->sort()->values()->all();
            $recibidos = collect(array_keys($datos['precios']))->map(fn ($id) => (string) $id)->sort()->values()->all();
            if ($esperados !== $recibidos || $detalles->isEmpty()) {
                $this->error('precios', 'Indica exactamente el precio final de cada mueble.');
            }
            $antes = $this->instantanea($cotizacion);
            $total = BigDecimal::zero();
            foreach ($detalles as $detalle) {
                $materiales = $detalle->materiales()->lockForUpdate()->get();
                if ($materiales->isEmpty() || $materiales->contains(function ($fila) {
                    $sinCantidad = $fila->materialesPedido()->exists() && BigDecimal::of($fila->cantidad_total_aplicada)->isZero();

                    return (! $sinCantidad && $fila->estado_costo !== 'conocido') || $fila->subtotal_aplicado === null;
                }) || $detalle->costo_total === null) {
                    $this->error('precios', 'Revisa los materiales con costo desconocido antes de aprobar.');
                }
                $detalle->precio_final = $this->dinero($datos['precios'][$detalle->id], 'precios.'.$detalle->id);
                $detalle->save();
                $total = $total->plus($detalle->precio_final);
            }
            // Conservar los datos de cotizaciones anteriores; las nuevas no requieren cliente.
            if ($cotizacion->cliente_id !== null) {
                $cliente = Cliente::whereKey($cotizacion->cliente_id)->lockForUpdate()->firstOrFail();
                $cotizacion->datos_cliente_aprobados = $cliente->only(['nombre', 'tipo', 'telefono', 'email', 'direccion']);
            }
            $cotizacion->precio_final = $this->importe($total, 'precios');
            $cotizacion->estado = 'aprobada';
            $cotizacion->validado_por_id = $actor->id;
            $cotizacion->validado_at = now();
            $cotizacion->aprobado_por_id = $actor->id;
            $cotizacion->aprobado_at = $cotizacion->validado_at;
            $cotizacion->version++;
            $cotizacion->save();
            app(AuditoriaService::class)->registrar($actor, $cotizacion, 'validado_y_aprobado', $antes, $this->instantanea($cotizacion));

            return $cotizacion->fresh(['detalles.materiales', 'cliente', 'aprobador']);
        }, 5);
    }

    private function instantanea(Cotizacion $cotizacion): array
    {
        $datos = $cotizacion->only($cotizacion->getFillable());
        $datos['detalles'] = $cotizacion->detalles()->with('materiales')->get()->toArray();

        return $datos;
    }

    private function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
