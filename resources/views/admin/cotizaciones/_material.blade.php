@php
    $esMaterialPedido = $cotizacion->pedido && $cotizacion->pedido->detalles->flatMap->materiales->contains('material_cotizacion_id', $datos['id'] ?? null);
    $materialPedido = $esMaterialPedido ? $cotizacion->pedido->detalles->flatMap->materiales->firstWhere('material_cotizacion_id', $datos['id'] ?? null) : null;
    $retirar = !empty($datos['retirar']);
    $requiereMotivoRetiro = $materialPedido?->cantidad_utilizada !== null && $materialPedido && Brick\Math\BigDecimal::of($materialPedido->cantidad_utilizada)->isPositive();
    $puedeRetirar = !$esMaterialPedido || ($cotizacion->pedido->estado !== 'en_fabricacion' && !$requiereMotivoRetiro) || auth()->user()->can('registrarUsoMateriales', $cotizacion->pedido);
    $prefijo = "detalles[$indice][materiales][$indiceMaterial]";
    $campo = "detalles.$indice.materiales.$indiceMaterial";
    $idBase = "m-$indice-$indiceMaterial";
    $mostrarAjuste = !empty($datos['motivo_ajuste_cantidad']) || $errors->has($campo.'.cantidad_total_aplicada') || $errors->has($campo.'.motivo_ajuste_cantidad');
@endphp
<div class="quote-material-row" data-material-row data-material-pedido="{{ $esMaterialPedido ? '1' : '0' }}">
    @if(!empty($datos['id']))
        <input type="hidden" name="{{ $prefijo }}[id]" value="{{ $datos['id'] }}">
    @endif
    <input type="hidden" name="{{ $prefijo }}[componente]" value="{{ $datos['componente'] ?? 'General' }}">
    <input type="hidden" name="{{ $prefijo }}[estado_costo]" value="{{ $datos['estado_costo'] ?? 'pendiente' }}" data-estado-costo>
    <input type="hidden" name="{{ $prefijo }}[fuente_costo]" value="{{ $datos['fuente_costo'] ?? 'referencia_catalogo' }}" data-fuente-costo>
    @if($esMaterialPedido)
        <input type="hidden" name="{{ $prefijo }}[retirar]" value="{{ $retirar ? '1' : '0' }}" data-retirar-material>
        <div class="material-retirement-note mb-3" data-retiro-material-panel @if(!$retirar) hidden @endif>
            <p class="mb-2"><i class="fas fa-minus-circle mr-2" aria-hidden="true"></i><strong>Material retirado:</strong> se conserva como no utilizado, con cantidad y subtotal en cero.</p>
            <label for="{{ $idBase }}-motivo-retiro">Motivo del retiro {{ $requiereMotivoRetiro ? '*' : '(opcional)' }}</label>
            <input id="{{ $idBase }}-motivo-retiro" name="{{ $prefijo }}[motivo_retiro]" value="{{ $datos['motivo_retiro'] ?? '' }}" class="form-control @error($campo.'.motivo_retiro') is-invalid @enderror" maxlength="2000" data-motivo-retiro data-requiere-motivo="{{ $requiereMotivoRetiro ? '1' : '0' }}" @required($retirar && $requiereMotivoRetiro)>
            @error($campo.'.motivo_retiro')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            @error($campo.'.retirar')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>
    @endif
    <div class="quote-material-grid">
        <div>
            <label for="{{ $idBase }}-material_id"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Material <span class="text-danger">*</span></label>
            <label class="sr-only" for="{{ $idBase }}-buscar">Buscar material</label>
            <input @disabled($esMaterialPedido) type="search" id="{{ $idBase }}-buscar" class="form-control form-control-sm mb-1" placeholder="Buscar material…" autocomplete="off" data-buscar-material>
            @if($esMaterialPedido)<input type="hidden" name="{{ $prefijo }}[material_id]" value="{{ $datos['material_id'] }}">@endif
            <select @disabled($esMaterialPedido) id="{{ $idBase }}-material_id" name="{{ $prefijo }}[material_id]" class="form-control @error($campo.'.material_id') is-invalid @enderror" required data-material-select>
                <option value="">Selecciona un material</option>
                @foreach($materiales as $material)
                    <option value="{{ $material->id }}" data-precio="{{ $material->precio_referencia }}" data-unidad="{{ $material->unidad->nombre }}" @selected((string)($datos['material_id'] ?? '') === (string)$material->id)>{{ $material->nombre }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted" data-busqueda-resultado aria-live="polite"></small>
            @error($campo.'.material_id')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
            @error($campo.'.componente')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
            @error($campo.'.fuente_costo')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
        </div>
        <div>
            <label for="{{ $idBase }}-cantidad_unitaria"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Cantidad por mueble <span class="text-danger">*</span></label>
            <input id="{{ $idBase }}-cantidad_unitaria" name="{{ $prefijo }}[cantidad_unitaria]" type="text" value="{{ App\Support\Decimal::entrada($datos['cantidad_unitaria'] ?? '') }}" class="form-control @error($campo.'.cantidad_unitaria') is-invalid @enderror" required inputmode="decimal" data-cantidad-unitaria>
            <small class="form-text text-muted">Unidad: <span data-unidad-material>—</span></small>
            @error($campo.'.cantidad_unitaria')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
        </div>
        <div>
            <label for="{{ $idBase }}-costo_unitario_estimado"><i class="fas fa-coins field-label-icon" aria-hidden="true"></i>Costo unitario (Bs)</label>
            <input id="{{ $idBase }}-costo_unitario_estimado" name="{{ $prefijo }}[costo_unitario_estimado]" type="text" value="{{ App\Support\Decimal::entrada($datos['costo_unitario_estimado'] ?? '') }}" class="form-control @error($campo.'.costo_unitario_estimado') is-invalid @enderror" inputmode="decimal" data-costo-material aria-describedby="{{ $idBase }}-costo-ayuda">
            <small id="{{ $idBase }}-costo-ayuda" class="form-text text-muted" data-costo-ayuda>Vacío: costo pendiente.</small>
            @error($campo.'.costo_unitario_estimado')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
            @error($campo.'.estado_costo')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="quote-material-subtotal">
            <span class="d-block font-weight-bold mb-2">Subtotal</span>
            <strong data-subtotal-material aria-live="polite">—</strong>
            <small class="form-text text-muted">Cantidad calculada: <span data-nominal-material>—</span></small>
        </div>
        <div class="quote-material-actions">

            <button type="button" class="btn btn-sm btn-danger" data-quitar-material @disabled(!$puedeRetirar) @if(!$puedeRetirar) title="Necesitas permiso para corregir el uso de este material." @endif><i class="fas {{ $retirar ? 'fa-undo' : 'fa-trash' }} mr-2" aria-hidden="true"></i>{{ $retirar ? 'Deshacer retiro' : 'Quitar' }}</button>

        </div>
    </div>
    <details class="quote-material-adjustment mt-2" data-ajuste-material @if($mostrarAjuste) open @endif>
        <summary>Ajustar cantidad total</summary>
        <div class="row mt-3">
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="{{ $idBase }}-cantidad_total_aplicada"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Cantidad total ajustada</label>
                    <input id="{{ $idBase }}-cantidad_total_aplicada" name="{{ $prefijo }}[cantidad_total_aplicada]" type="text" value="{{ App\Support\Decimal::entrada($datos['cantidad_total_aplicada'] ?? '') }}" class="form-control @error($campo.'.cantidad_total_aplicada') is-invalid @enderror" inputmode="decimal" data-cantidad-aplicada>
                    <small class="form-text text-muted">Para todos los muebles de este detalle. Vacío usa el total calculado.</small>
                    @error($campo.'.cantidad_total_aplicada')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-8">
                <div class="form-group">
                    <label for="{{ $idBase }}-motivo_ajuste_cantidad"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Motivo del ajuste</label>
                    <textarea id="{{ $idBase }}-motivo_ajuste_cantidad" name="{{ $prefijo }}[motivo_ajuste_cantidad]" class="form-control @error($campo.'.motivo_ajuste_cantidad') is-invalid @enderror" rows="2" maxlength="2000" data-motivo-cantidad>{{ $datos['motivo_ajuste_cantidad'] ?? '' }}</textarea>
                    @error($campo.'.motivo_ajuste_cantidad')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </details>
</div>
