<?php

namespace App\Services;

use App\Models\CasoHistorico;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\Material;
use App\Models\TipoMueble;
use App\Models\User;
use App\Support\Acceso;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecuperacionCasosService
{
    public function buscar(User $actor, Cotizacion $cotizacion, DetalleCotizacion $detalle): array
    {
        $actor = $actor->fresh();
        Gate::forUser($actor)->authorize('view', $cotizacion);
        abort_unless(Acceso::permite($actor, 'casos.consultar') && Acceso::permite($actor, 'costos.consultar'), 403);
        abort_unless($detalle->cotizacion_id === $cotizacion->id, 404);
        $detalle = DetalleCotizacion::whereKey($detalle->id)->where('cotizacion_id', $cotizacion->id)->with('tipo')->firstOrFail();

        return $this->compararDetalle($detalle);
    }

    /** Consulta temporal: no crea cotizaciones, detalles ni auditorías. */
    public function buscarFormulario(User $actor, array $datos, ?Cotizacion $cotizacion = null): array
    {
        $actor = $actor->fresh();
        Gate::forUser($actor)->authorize($cotizacion ? 'update' : 'create', $cotizacion ?? Cotizacion::class);
        abort_unless(Acceso::permite($actor, 'cotizaciones.consultar') && Acceso::permite($actor, 'casos.consultar') && Acceso::permite($actor, 'costos.consultar'), 403);
        $campos = ['tipo_mueble_id', 'material_principal_id', ...ComparadorCasosService::NUMERICOS, 'acabado'];
        if (array_diff(array_keys($datos), $campos)) {
            throw ValidationException::withMessages(['mueble' => 'Envía solamente las características del mueble.']);
        }
        $datos = Validator::make($datos, [
            'tipo_mueble_id' => ['required', 'integer', 'exists:tipos_mueble,id'],
            'material_principal_id' => ['required', 'integer', 'exists:materiales,id'],
            'largo_cm' => ['required'],
            'ancho_cm' => ['required'],
            'alto_cm' => ['required'],
            'cantidad_muebles' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'puertas' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'cajones' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'divisiones' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'acabado' => ['required', 'string', Rule::in(config('tajibo.acabados'))],
        ])->validate();
        foreach (ComparadorCasosService::NUMERICOS as $campo) {
            app(ComparadorCasosService::class)->numero($datos, $campo);
        }
        $datos['tipo_mueble_id'] = (int) $datos['tipo_mueble_id'];
        $datos['material_principal_id'] = (int) $datos['material_principal_id'];
        $tipo = TipoMueble::whereKey($datos['tipo_mueble_id'])->where('activo', true)->first();
        $principal = Material::principales()->whereKey($datos['material_principal_id'])->first();
        if (! $tipo || ! $principal) {
            throw ValidationException::withMessages(['mueble' => 'Selecciona un tipo activo y un material de la categoría Material principal.']);
        }
        $detalle = new DetalleCotizacion;
        foreach ($datos as $campo => $valor) {
            $detalle->{$campo} = $valor;
        }
        $detalle->material_principal_nombre = $principal->nombre;
        $detalle->setRelation('tipo', $tipo);

        return $this->compararDetalle($detalle);
    }

    private function compararDetalle(DetalleCotizacion $detalle): array
    {
        $salida = ['estado' => 'sin_antecedentes', 'mensaje' => 'No hay antecedentes suficientemente similares.', 'resultados' => [], 'perfil' => null, 'fuera_cobertura' => []];
        if (! config('similitud.habilitado')) {
            return [...$salida, 'estado' => 'desactivado', 'mensaje' => 'La búsqueda de similitud está desactivada.'];
        }
        $consulta = $this->vector($detalle);
        $casos = CasoHistorico::with(['tipoMueble', 'materialPrincipal', 'materiales.unidad', 'materiales.material'])->orderBy('id')->get();
        if (! config('similitud.perfil')) {
            return [...$salida, 'estado' => 'sin_perfil', 'mensaje' => 'El perfil de casos históricos requiere configuración antes de buscar referencias.'];
        }
        try {
            $perfil = $this->cargarPerfil($casos);
        } catch (\RuntimeException|\InvalidArgumentException|\JsonException|ValidationException $error) {
            return [...$salida, 'estado' => 'perfil_invalido', 'mensaje' => 'El perfil de comparación requiere revisión: cambió su configuración o alguna referencia dejó de ser válida.'];
        }
        $salida['perfil'] = ['version' => $perfil['version'], 'huella' => $perfil['huella'], 'umbral' => $perfil['parametros']['umbral'], 'k' => $perfil['parametros']['k']];
        $rangos = $perfil['rangos'][$detalle->tipo->codigo] ?? null;
        if (! $rangos) {
            return [...$salida, 'estado' => 'sin_cobertura', 'mensaje' => 'No hay un perfil de referencia para este tipo de mueble.'];
        }
        $comparador = app(ComparadorCasosService::class);
        $fuera = $comparador->fueraDeCobertura($consulta, $rangos);
        if ($fuera) {
            return [...$salida, 'estado' => 'sin_cobertura', 'fuera_cobertura' => $fuera, 'mensaje' => 'Las características están fuera de los rangos cubiertos por el perfil. Continúa manualmente; no se extrapolan materiales.'];
        }
        $ordenados = [];
        foreach ($casos->where('tipo_mueble_id', $detalle->tipo_mueble_id) as $caso) {
            if (! $this->completo($caso)) {
                continue;
            }
            if ($comparador->fueraDeCobertura($this->vector($caso), $rangos)) {
                continue;
            }
            $comparacion = $comparador->comparar($consulta, $this->vector($caso), $rangos, $perfil['parametros']['pesos']);
            if ($comparacion['puntaje']->isLessThan($perfil['parametros']['umbral'])) {
                continue;
            }
            $ordenados[] = ['caso' => $caso, ...$comparacion];
        }
        usort($ordenados, fn ($a, $b) => $b['puntaje']->compareTo($a['puntaje']) ?: ($a['caso']->id <=> $b['caso']->id));
        foreach (array_slice($ordenados, 0, min(3, $perfil['parametros']['k'], $perfil['parametros']['maximo_resultados'])) as $entrada) {
            $caso = $entrada['caso'];
            $salida['resultados'][] = ['id' => $caso->id, 'tipo' => $caso->tipo_nombre, 'material_principal' => $caso->material_principal_nombre, 'acabado' => $caso->acabado, 'origen' => $caso->origen, 'revision' => $caso->estado_revision, 'cantidad_muebles' => $caso->cantidad_muebles, 'porcentaje' => $entrada['porcentaje'], 'diferencias' => $entrada['diferencias'], 'costo_materiales' => $caso->costo_materiales_estimado_total, 'mano_obra' => $caso->mano_obra_estimada_total, 'otros_costos' => $caso->otros_costos_estimados_total, 'costo_total' => $caso->costo_estimado_total, 'precio_grupo' => $caso->precio_historico_total, 'materiales' => $caso->materiales->map(fn ($m) => ['nombre' => $m->material_nombre, 'unidad' => $m->unidad_nombre, 'cantidad' => $m->cantidad_total, 'inactivo' => ! $m->material->activo])->all()];
        }
        if ($salida['resultados']) {
            $salida['estado'] = 'encontrados';
            $salida['mensaje'] = 'Casos históricos que cumplen el perfil de comparación. Revisa sus estimaciones antes de utilizarlas.';
        }

        return $salida;
    }

    private function vector($registro): array
    {
        return $registro->only(['tipo_mueble_id', 'material_principal_id', 'material_principal_nombre', ...ComparadorCasosService::NUMERICOS, 'acabado']);
    }

    private function completo(CasoHistorico $caso): bool
    {
        if ($caso->motivo_no_elegible || ! in_array($caso->estado_revision, ['pendiente', 'revisado'], true)) {
            return false;
        }
        try {
            $comparador = app(ComparadorCasosService::class);
            foreach (ComparadorCasosService::NUMERICOS as $campo) {
                $comparador->numero($this->vector($caso), $campo);
            }
            if (! $caso->tipoMueble || ! $caso->materialPrincipal || ! is_string($caso->acabado) || trim($caso->acabado) === '') {
                return false;
            }
            foreach (['mano_obra_estimada_total', 'otros_costos_estimados_total', 'costo_materiales_estimado_total', 'costo_estimado_total', 'precio_historico_total'] as $campo) {
                Decimal::numero($caso->{$campo}, 2, $campo, $campo === 'precio_historico_total');
            }
            $suma = BigDecimal::zero();
            $principal = false;
            foreach ($caso->materiales as $material) {
                if (! $material->unidad || ! $material->material || $material->unidad_id !== $material->material->unidad_id) {
                    return false;
                }
                $cantidad = Decimal::cantidad($material->cantidad_total, $material->unidad, 'cantidad', false);
                if ($cantidad->isPositive()) {
                    $costo = Decimal::numero($material->costo_unitario_estimado, 6, 'costo_unitario', true);
                    $subtotal = $cantidad->multipliedBy($costo)->toScale(2, RoundingMode::HALF_UP);
                    if ($material->subtotal_estimado === null || ! $subtotal->isEqualTo($material->subtotal_estimado)) {
                        return false;
                    }
                    $suma = $suma->plus($subtotal);
                    if ($material->material_id === $caso->material_principal_id) {
                        $principal = true;
                    }
                }
            }

            return $principal && $suma->isEqualTo($caso->costo_materiales_estimado_total) && $suma->plus($caso->mano_obra_estimada_total)->plus($caso->otros_costos_estimados_total)->isEqualTo($caso->costo_estimado_total);
        } catch (ValidationException|MathException $error) {
            return false;
        }
    }

    private function cargarPerfil($casos): array
    {
        $config = config('similitud.perfil');
        $archivo = is_file($config['archivo'] ?? '') ? $config['archivo'] : config_path($config['archivo'] ?? '');
        if (! is_file($archivo) || hash_file('sha256', $archivo) !== ($config['sha256'] ?? null)) {
            throw new \RuntimeException('Archivo de perfil inválido.');
        }
        $perfil = json_decode(file_get_contents($archivo), true, 512, JSON_THROW_ON_ERROR);
        $parametros = [];
        foreach (['distancia', 'normalizacion', 'pesos', 'k', 'umbral', 'maximo_resultados'] as $campo) {
            $parametros[$campo] = config('similitud.'.$campo);
        }
        if (empty($perfil['version']) || ($perfil['parametros'] ?? null) != $parametros || $parametros['distancia'] !== 'gower_ponderada' || $parametros['normalizacion'] !== 'rangos_fijos_por_tipo' || ! is_int($parametros['k']) || $parametros['k'] < 1 || $parametros['k'] > 3 || $parametros['maximo_resultados'] !== 3 || ! is_array($parametros['pesos'])) {
            throw new \RuntimeException('Parámetros sin perfil compatible.');
        }
        $umbral = Decimal::numero($parametros['umbral'], 4, 'umbral', true);
        if ($umbral->isGreaterThan(100)) {
            throw new \RuntimeException('Umbral inválido.');
        }
        if (array_diff(array_keys(ComparadorCasosService::ETIQUETAS), array_keys($parametros['pesos'])) || count($parametros['pesos']) !== 9 || array_sum($parametros['pesos']) !== 100) {
            throw new \RuntimeException('Pesos inválidos.');
        }
        foreach ($parametros['pesos'] as $peso) {
            if (! is_int($peso) || $peso <= 0) {
                throw new \RuntimeException('Pesos inválidos.');
            }
        }
        $observados = [];
        $vinculos = [];
        $principales = [];
        $ids = [];
        $comparador = app(ComparadorCasosService::class);
        if (empty($perfil['referencias']) || ! is_array($perfil['referencias'])) {
            throw new \RuntimeException('Faltan referencias.');
        }
        if (! is_array($perfil['rangos'] ?? null)) {
            throw new \RuntimeException('Rangos incompletos.');
        }
        foreach ($perfil['referencias'] as $referencia) {
            $requeridos = [...ComparadorCasosService::NUMERICOS, 'tipo_codigo', 'acabado', 'posicion_lote', 'principal'];
            if (! is_array($referencia) || array_diff($requeridos, array_keys($referencia)) || ! is_string($referencia['tipo_codigo']) || ! is_string($referencia['acabado'])) {
                throw new \RuntimeException('Referencia incompleta.');
            }
            if ((! is_int($referencia['posicion_lote']) || ! is_string($referencia['principal']) || trim($referencia['principal']) === '')) {
                throw new \RuntimeException('Referencia inválida.');
            }
            $caso = $casos->first(fn ($c) => $c->lote === ($perfil['lote'] ?? null) && $c->posicion_lote === ($referencia['posicion_lote'] ?? null));
            if (! $caso || isset($ids[$caso->id]) || ! $this->completo($caso)) {
                throw new \RuntimeException('Referencia ausente, descartada o incompleta.');
            }
            $ids[$caso->id] = true;
            if ($caso->tipoMueble->codigo !== $referencia['tipo_codigo'] || $caso->acabado !== $referencia['acabado']) {
                throw new \RuntimeException('Descriptor de referencia diferente.');
            }

            $alias = $referencia['principal'];
            foreach ([$caso->material_principal_nombre, $caso->materialPrincipal->nombre] as $nombre) {
                if ($nombre !== $alias && ! str_starts_with($nombre, $alias.' estándar')) {
                    throw new \RuntimeException('Identidad del material cambió.');
                }
            }
            if (isset($principales[$alias]) && $principales[$alias] !== $caso->material_principal_id) {
                throw new \RuntimeException('Vínculo ambiguo de material principal.');
            }
            $principales[$alias] = $caso->material_principal_id;

            foreach (ComparadorCasosService::NUMERICOS as $campo) {
                $valor = $comparador->numero($this->vector($caso), $campo);
                if (! $valor->isEqualTo($comparador->numero($referencia, $campo))) {
                    throw new \RuntimeException('Características de referencia distintas.');
                }
                $r = &$observados[$referencia['tipo_codigo']][$campo];
                if (! $r) {
                    $r = ['min' => (string) $valor, 'max' => (string) $valor];
                }
                if ($valor->isLessThan($r['min'])) {
                    $r['min'] = (string) $valor;
                }
                if ($valor->isGreaterThan($r['max'])) {
                    $r['max'] = (string) $valor;
                }
                unset($r);
            }
            $vinculos[] = ['id' => $caso->id, 'tipo_id' => $caso->tipo_mueble_id, 'principal_id' => $caso->material_principal_id];
        }
        if (count($principales) !== count(array_unique($principales))) {
            throw new \RuntimeException('Materiales principales ambiguos.');
        }
        if (array_diff(array_keys($observados), array_keys($perfil['rangos'] ?? [])) || count($observados) !== count($perfil['rangos'] ?? [])) {
            throw new \RuntimeException('Rangos por tipo incompatibles.');
        }
        foreach ($observados as $tipo => $campos) {
            foreach ($campos as $campo => $limites) {
                foreach (['min', 'max'] as $extremo) {
                    if (! isset($perfil['rangos'][$tipo][$campo][$extremo]) || ! BigDecimal::of($limites[$extremo])->isEqualTo(Decimal::numero($perfil['rangos'][$tipo][$campo][$extremo], 4, 'rango'))) {
                        throw new \RuntimeException('Rangos distintos a las referencias congeladas.');
                    }
                }
            }
        }
        $perfil['huella'] = hash('sha256', $config['sha256'].'|'.json_encode($vinculos, JSON_THROW_ON_ERROR));

        return $perfil;
    }
}
