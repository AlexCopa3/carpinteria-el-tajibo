@php
    $esMueblePedido = $cotizacion->pedido && $cotizacion->pedido->detalles->contains('detalle_cotizacion_id', $datos['id'] ?? null);
    $prefijo = "detalles[$indice]";
    $campo = "detalles.$indice";
    $idBase = "d-$indice";
    $mostrarComponentes = ($datos['puertas'] ?? 0) > 0 || ($datos['cajones'] ?? 0) > 0 || ($datos['divisiones'] ?? 0) > 0 || $errors->has($campo.'.puertas') || $errors->has($campo.'.cajones') || $errors->has($campo.'.divisiones');
    $mostrarCostosOpcionales = !empty($datos['motivo_ajuste_mano_obra']) || isset($datos['precio_propuesto']) || $errors->has($campo.'.mano_obra_total_aplicada') || $errors->has($campo.'.motivo_ajuste_mano_obra') || $errors->has($campo.'.precio_propuesto');
@endphp
<section class="card quote-detail form-panel form-themed {{ $cotizacion->exists ? 'form-action-update' : 'form-action-create' }}" data-detalle>
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title">Mueble <span data-numero-mueble>{{ is_numeric($indice) ? $indice + 1 : '' }}</span></h2>
        @if(!$esMueblePedido)
        <button class="btn btn-sm btn-danger ml-auto" type="button" data-quitar-detalle><i class="fas fa-trash mr-2" aria-hidden="true"></i>Quitar mueble</button>
        @endif
    </div>
    <div class="card-body">
        @if(!empty($datos['id']))<input type="hidden" name="{{ $prefijo }}[id]" value="{{ $datos['id'] }}">@endif
        @if($esMueblePedido)<p class="small text-muted">Mueble existente: conserva su diseño y materiales; puedes aumentar la cantidad y revisar costos. Para otro diseño, añade un mueble.</p>@endif
        <div class="quote-step-heading"><span class="quote-step-number">1</span><div><h3>Describe el mueble</h3><p>Indica cómo será un mueble y cuántos iguales necesitas.</p></div></div>
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="{{ $idBase }}-tipo_mueble_id"><i class="fas fa-chair field-label-icon" aria-hidden="true"></i>Tipo de mueble <span class="text-danger">*</span></label>
                    @if($esMueblePedido)<input type="hidden" name="{{ $prefijo }}[tipo_mueble_id]" value="{{ $datos['tipo_mueble_id'] }}">@endif
                    <select @disabled($esMueblePedido) id="{{ $idBase }}-tipo_mueble_id" name="{{ $prefijo }}[tipo_mueble_id]" class="form-control" required>
                    <option value="">Selecciona un tipo</option>
                    @foreach($tipos as $tipo)<option value="{{ $tipo->id }}" @selected((string)($datos['tipo_mueble_id'] ?? '') === (string)$tipo->id)>{{ $tipo->nombre }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="{{ $idBase }}-cantidad_muebles"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Cantidad de muebles iguales <span class="text-danger">*</span></label>
                    <input id="{{ $idBase }}-cantidad_muebles" name="{{ $prefijo }}[cantidad_muebles]" type="number" value="{{ $datos['cantidad_muebles'] ?? 1 }}" class="form-control @error($campo.'.cantidad_muebles') is-invalid @enderror" required min="1" max="4294967295" step="1" data-cantidad-muebles>
                    @error($campo.'.cantidad_muebles')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="{{ $idBase }}-material_principal_id"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Material principal <span class="text-danger">*</span></label>
                    @php
                        $detalleAnterior = $cotizacion->exists ? $cotizacion->detalles->firstWhere('id', $datos['id'] ?? null) : null;
                        $principalAnterior = $detalleAnterior?->material_principal_id;
                    @endphp
                    @if($esMueblePedido)<input type="hidden" name="{{ $prefijo }}[material_principal_id]" value="{{ $datos['material_principal_id'] }}">@endif
                    <select @disabled($esMueblePedido) id="{{ $idBase }}-material_principal_id" name="{{ $prefijo }}[material_principal_id]" class="form-control @error($campo.'.material_principal_id') is-invalid @enderror" required aria-describedby="{{ $idBase }}-principal-help @error($campo.'.material_principal_id') {{ $idBase }}-principal-error @enderror" @error($campo.'.material_principal_id') aria-invalid="true" @enderror>
                    <option value="">Selecciona el material principal</option>
                    @if($principalAnterior && !$principales->contains('id', $principalAnterior) && $materiales->contains('id', $principalAnterior))
                        <option value="{{ $principalAnterior }}" @selected((string)($datos['material_principal_id'] ?? '') === (string)$principalAnterior)>{{ $detalleAnterior->material_principal_nombre }} (registro anterior)</option>
                    @endif
                    @foreach($principales as $material)<option value="{{ $material->id }}" @selected((string)($datos['material_principal_id'] ?? '') === (string)$material->id)>{{ $material->nombre }}</option>@endforeach
                    </select>
                    <small id="{{ $idBase }}-principal-help" class="form-text text-muted">Solo materiales de la categoría «Material principal».</small>
                    @error($campo.'.material_principal_id')<span id="{{ $idBase }}-principal-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="{{ $idBase }}-largo_cm"><i class="fas fa-ruler-horizontal field-label-icon" aria-hidden="true"></i>Largo (cm) <span class="text-danger">*</span></label>
                    <input @readonly($esMueblePedido) id="{{ $idBase }}-largo_cm" name="{{ $prefijo }}[largo_cm]" type="text" value="{{ App\Support\Decimal::entrada($datos['largo_cm'] ?? '') }}" class="form-control @error($campo.'.largo_cm') is-invalid @enderror" required inputmode="decimal">
                    @error($campo.'.largo_cm')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="{{ $idBase }}-ancho_cm"><i class="fas fa-ruler-combined field-label-icon" aria-hidden="true"></i>Ancho (cm) <span class="text-danger">*</span></label>
                    <input @readonly($esMueblePedido) id="{{ $idBase }}-ancho_cm" name="{{ $prefijo }}[ancho_cm]" type="text" value="{{ App\Support\Decimal::entrada($datos['ancho_cm'] ?? '') }}" class="form-control @error($campo.'.ancho_cm') is-invalid @enderror" required inputmode="decimal">
                    @error($campo.'.ancho_cm')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="{{ $idBase }}-alto_cm"><i class="fas fa-ruler-vertical field-label-icon" aria-hidden="true"></i>Alto (cm) <span class="text-danger">*</span></label>
                    <input @readonly($esMueblePedido) id="{{ $idBase }}-alto_cm" name="{{ $prefijo }}[alto_cm]" type="text" value="{{ App\Support\Decimal::entrada($datos['alto_cm'] ?? '') }}" class="form-control @error($campo.'.alto_cm') is-invalid @enderror" required inputmode="decimal">
                    @error($campo.'.alto_cm')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="{{ $idBase }}-acabado"><i class="fas fa-paint-brush field-label-icon" aria-hidden="true"></i>Acabado <span class="text-danger">*</span></label>
                    @php
                        $acabados = config('tajibo.acabados');
                        $acabadoSeleccionado = $datos['acabado'] ?? '';
                        $acabadoAnterior = $cotizacion->exists ? $cotizacion->detalles->firstWhere('id', $datos['id'] ?? null)?->acabado : null;
                    @endphp
                    @if($esMueblePedido)<input type="hidden" name="{{ $prefijo }}[acabado]" value="{{ $datos['acabado'] }}">@endif
                    <select @disabled($esMueblePedido) id="{{ $idBase }}-acabado" name="{{ $prefijo }}[acabado]" class="form-control @error($campo.'.acabado') is-invalid @enderror" required aria-describedby="{{ $idBase }}-acabado-help @error($campo.'.acabado') {{ $idBase }}-acabado-error @enderror" @error($campo.'.acabado') aria-invalid="true" @enderror>
                    <option value="">Selecciona un acabado</option>
                    @if($acabadoAnterior && !in_array($acabadoAnterior, $acabados, true))
                        <option value="{{ $acabadoAnterior }}" @selected($acabadoSeleccionado === $acabadoAnterior)>{{ $acabadoAnterior }} (registro anterior)</option>
                    @endif
                    @foreach($acabados as $acabado)
                        <option value="{{ $acabado }}" @selected($acabadoSeleccionado === $acabado)>{{ $acabado }}</option>
                    @endforeach
                    </select>
                    <small id="{{ $idBase }}-acabado-help" class="form-text text-muted">El color y los detalles pueden indicarse en Observaciones del mueble.</small>
                    @error($campo.'.acabado')<span id="{{ $idBase }}-acabado-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="{{ $cotizacion->exists ? 'col-12' : 'col-12 col-md-6' }} quote-components-column">
                <details class="quote-options mb-3" data-componentes-mueble @if($mostrarComponentes) open @endif>
                    <summary><i class="fas fa-th-large mr-2" aria-hidden="true"></i>Puertas, cajones y divisiones <span class="quote-optional">Si corresponde</span></summary>
                    <p class="text-muted mt-3 mb-2">Deja 0 cuando el mueble no tenga alguno de estos elementos.</p>
                    <div class="row mt-2">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="{{ $idBase }}-puertas"><i class="fas fa-door-closed field-label-icon" aria-hidden="true"></i>Puertas <span class="text-danger">*</span></label>
                                <input @readonly($esMueblePedido) id="{{ $idBase }}-puertas" name="{{ $prefijo }}[puertas]" type="number" value="{{ $datos['puertas'] ?? 0 }}" class="form-control @error($campo.'.puertas') is-invalid @enderror" required min="0" step="1">
                                @error($campo.'.puertas')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="{{ $idBase }}-cajones"><i class="fas fa-archive field-label-icon" aria-hidden="true"></i>Cajones <span class="text-danger">*</span></label>
                                <input @readonly($esMueblePedido) id="{{ $idBase }}-cajones" name="{{ $prefijo }}[cajones]" type="number" value="{{ $datos['cajones'] ?? 0 }}" class="form-control @error($campo.'.cajones') is-invalid @enderror" required min="0" step="1">
                                @error($campo.'.cajones')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="{{ $idBase }}-divisiones"><i class="fas fa-th-large field-label-icon" aria-hidden="true"></i>Divisiones <span class="text-danger">*</span></label>
                                <input @readonly($esMueblePedido) id="{{ $idBase }}-divisiones" name="{{ $prefijo }}[divisiones]" type="number" value="{{ $datos['divisiones'] ?? 0 }}" class="form-control @error($campo.'.divisiones') is-invalid @enderror" required min="0" step="1">
                                @error($campo.'.divisiones')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
        <details class="quote-options mb-4" @if(!empty($datos['observaciones']) || $errors->has($campo.'.observaciones')) open @endif>
            <summary><i class="far fa-comment-alt mr-2" aria-hidden="true"></i>Color y observaciones del mueble <span class="quote-optional">Opcional</span></summary>
            <div class="row mt-3">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label for="{{ $idBase }}-observaciones"><i class="fas fa-chair field-label-icon" aria-hidden="true"></i>Observaciones del mueble</label>
                        <textarea id="{{ $idBase }}-observaciones" name="{{ $prefijo }}[observaciones]" class="form-control @error($campo.'.observaciones') is-invalid @enderror" rows="2" maxlength="2000">{{ $datos['observaciones'] ?? '' }}</textarea>
                        @error($campo.'.observaciones')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </details>
        @if(config('similitud.habilitado') && App\Support\Acceso::permite(auth()->user(), 'casos.consultar') && App\Support\Acceso::permite(auth()->user(), 'cotizaciones.consultar') && App\Support\Acceso::permite(auth()->user(), 'costos.consultar'))
            <section class="quote-options p-3 mb-4" aria-label="Búsqueda de casos del mueble">
                <div class="quote-step-heading"><span class="quote-step-number">2</span><div><h3>Busca una referencia</h3><p>Solo necesitas las características anteriores. Todavía no llenes materiales ni costos.</p></div></div>
                <div class="table-actions flex-wrap mb-3">
                @if(!$esMueblePedido)
                    <button type="button" class="btn btn-primary" data-buscar-casos><i class="fas fa-search mr-2" aria-hidden="true"></i>Buscar casos similares</button>
                @endif
                @if(!$esMueblePedido)
                    <button type="button" class="btn btn-cancel" data-llenado-manual><i class="fas fa-pen mr-2" aria-hidden="true"></i>Continuar manualmente</button>
                @endif
                </div>
                <div data-estado-busqueda role="status" aria-live="polite"></div>
                <div data-resultados-casos></div>
            </section>
        @endif
        <div data-propuesta-aplicada class="alert alert-info" hidden></div>
        <input type="hidden" name="{{ $prefijo }}[referencia_caso]" value="{{ $datos['referencia_caso'] ?? '' }}" data-referencia-caso>
        <details class="quote-options mb-4" data-llenado-costos @if($cotizacion->exists || !empty($datos) || collect($errors->keys())->contains(fn($key) => str_starts_with($key, $campo.'.'))) open @endif>
            <summary><i class="fas fa-edit mr-2" aria-hidden="true"></i>Materiales y costos · revisar propuesta o llenar manualmente</summary>
            <div class="mt-3">
        <div class="quote-step-heading"><span class="quote-step-number">3</span><div><h3>Materiales por unidad de mueble</h3><p>El principal se añade automáticamente. Completa la cantidad para un solo mueble y añade los demás materiales.</p></div></div>
        <div data-materiales>
            @foreach(($datos['materiales'] ?? [[]]) as $indiceMaterial => $datosMaterial)
                @include('admin.cotizaciones._material', ['datos' => $datosMaterial])
            @endforeach
        </div>
        <button class="btn btn-primary mb-4" type="button" data-agregar-material><i class="fas fa-plus mr-2" aria-hidden="true"></i>Añadir material</button>
        <div class="quote-step-heading"><span class="quote-step-number">3</span><div><h3>Mano de obra y otros costos</h3><p>Escribe la mano de obra de un solo mueble. El sistema calcula el total.</p></div></div>
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="{{ $idBase }}-mano_obra_unitaria"><i class="fas fa-tools field-label-icon" aria-hidden="true"></i>Mano de obra por mueble (Bs) <span class="text-danger">*</span></label>
                    <input id="{{ $idBase }}-mano_obra_unitaria" name="{{ $prefijo }}[mano_obra_unitaria]" type="text" value="{{ App\Support\Decimal::entrada($datos['mano_obra_unitaria'] ?? '0') }}" class="form-control @error($campo.'.mano_obra_unitaria') is-invalid @enderror" required inputmode="decimal" data-mano-unitaria>
                    <small class="form-text text-muted">Ejemplo: Bs 80 por silla × 4 sillas = Bs 320.</small>
                    @error($campo.'.mano_obra_unitaria')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="{{ $idBase }}-otros_costos_total"><i class="fas fa-coins field-label-icon" aria-hidden="true"></i>Otros costos del detalle (total Bs) <span class="text-danger">*</span></label>
                    <input id="{{ $idBase }}-otros_costos_total" name="{{ $prefijo }}[otros_costos_total]" type="text" value="{{ App\Support\Decimal::entrada($datos['otros_costos_total'] ?? '0') }}" class="form-control @error($campo.'.otros_costos_total') is-invalid @enderror" required inputmode="decimal" data-otros-costos>
                    <small class="form-text text-muted">Transporte u otros gastos para todos estos muebles. Si no hay, deja 0.</small>
                    @error($campo.'.otros_costos_total')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
        <details class="quote-options mb-3" data-costos-opcionales @if($mostrarCostosOpcionales) open @endif>
            <summary><i class="fas fa-sliders-h mr-2" aria-hidden="true"></i>Ajuste de mano de obra y precio propuesto <span class="quote-optional">Opcional</span></summary>
            <p class="text-muted mt-3">Usa el ajuste solo si el trabajo conjunto cambia el costo total de mano de obra; explica el motivo.</p>
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label for="{{ $idBase }}-mano_obra_total_aplicada"><i class="fas fa-tools field-label-icon" aria-hidden="true"></i>Mano de obra total ajustada (opcional)</label>
                        <input id="{{ $idBase }}-mano_obra_total_aplicada" name="{{ $prefijo }}[mano_obra_total_aplicada]" type="text" value="{{ App\Support\Decimal::entrada($datos['mano_obra_total_aplicada'] ?? '') }}" class="form-control @error($campo.'.mano_obra_total_aplicada') is-invalid @enderror"  inputmode="decimal" data-mano-aplicada>
                        @error($campo.'.mano_obra_total_aplicada')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-12 col-md-8">
                    <div class="form-group">
                        <label for="{{ $idBase }}-motivo_ajuste_mano_obra"><i class="fas fa-tools field-label-icon" aria-hidden="true"></i>Justificación del ahorro o ajuste de mano de obra</label>
                        <textarea id="{{ $idBase }}-motivo_ajuste_mano_obra" name="{{ $prefijo }}[motivo_ajuste_mano_obra]" class="form-control @error($campo.'.motivo_ajuste_mano_obra') is-invalid @enderror" rows="2" maxlength="2000" data-motivo-mano>{{ $datos['motivo_ajuste_mano_obra'] ?? '' }}</textarea>
                        @error($campo.'.motivo_ajuste_mano_obra')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label for="{{ $idBase }}-precio_propuesto"><i class="fas fa-coins field-label-icon" aria-hidden="true"></i>Precio total propuesto (opcional Bs)</label>
                        <input id="{{ $idBase }}-precio_propuesto" name="{{ $prefijo }}[precio_propuesto]" type="text" value="{{ App\Support\Decimal::entrada($datos['precio_propuesto'] ?? '') }}" class="form-control @error($campo.'.precio_propuesto') is-invalid @enderror"  inputmode="decimal">
                        @error($campo.'.precio_propuesto')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </details>
        <div class="quote-cost-breakdown" aria-label="Resumen de costos del mueble">
            <div><span>Materiales · total</span><strong data-total-materiales>—</strong></div>
            <div><span>Mano de obra · total</span><strong data-total-mano>—</strong></div>
            <div><span>Otros costos · total</span><strong data-total-otros>—</strong></div>
            <div class="quote-cost-grand"><span>Costo de estos muebles</span><strong data-total-detalle>—</strong></div>
        </div>
        <small class="text-muted d-block mt-2">Mano de obra calculada antes de ajustes: <span data-nominal-mano>—</span>. Los importes incluyen la cantidad de muebles indicada.</small>
            </div>
        </details>
    </div>
</section>
