@php
    $prefijo = "materiales[$indice]";
    $campo = "materiales.$indice";
    $original = !empty($fila['id']) ? $filasOriginales->get((int) $fila['id']) : null;
    $planificado = $original?->cantidad_planificada ?? '0';
    $seleccionado = $materiales->firstWhere('id', $fila['material_id'] ?? null);
@endphp
<div class="quote-material-row" data-uso-fila>
    <input type="hidden" name="{{ $prefijo }}[detalle_pedido_id]" value="{{ $fila['detalle_pedido_id'] }}">
    @if(!empty($fila['id']))<input type="hidden" name="{{ $prefijo }}[id]" value="{{ $fila['id'] }}">@endif
    <div class="row uso-material-fields">
        <div class="col-12 col-md-5 form-group">
            <label for="uso-{{ $indice }}-material"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Material</label>
            @if(!empty($fila['id']))
                <input type="hidden" name="{{ $prefijo }}[material_id]" value="{{ $fila['material_id'] }}">
                <input id="uso-{{ $indice }}-material" class="form-control" value="{{ $original?->material_nombre ?? 'Fila sin referencia válida' }}" readonly>
                <small class="form-text text-muted">{{ $original?->unidad_nombre }}@if($original && $original->componente !== 'General') · {{ $original->componente }}@endif</small>
            @else
                <select id="uso-{{ $indice }}-material" name="{{ $prefijo }}[material_id]" class="form-control @error($campo.'.material_id') is-invalid @enderror" data-uso-material required>
                    <option value="">Selecciona un material</option>
                    @foreach($materiales as $material)
                        <option value="{{ $material->id }}" data-unidad="{{ $material->unidad->nombre }}" @selected((string)($fila['material_id'] ?? '') === (string)$material->id)>{{ $material->nombre }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted" data-uso-unidad>{{ $seleccionado?->unidad->nombre ?? 'Unidad automática' }}</small>
            @endif
            @error($campo.'.material_id')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
            @error($campo.'.id')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
            @error($campo.'.detalle_pedido_id')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="col-6 col-md-2 form-group">
            <label for="uso-{{ $indice }}-plan"><i class="fas fa-clipboard-list field-label-icon" aria-hidden="true"></i>Planificado</label>
            <input id="uso-{{ $indice }}-plan" class="form-control" value="{{ App\Support\Decimal::mostrar($planificado) }}" readonly>
        </div>
        <div class="col-6 col-md-3 form-group">
            <label for="uso-{{ $indice }}-cantidad"><i class="fas fa-tools field-label-icon" aria-hidden="true"></i>Utilizado total</label>
            <input id="uso-{{ $indice }}-cantidad" name="{{ $prefijo }}[cantidad_utilizada]" class="form-control @error($campo.'.cantidad_utilizada') is-invalid @enderror" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,4})?" maxlength="20" value="{{ App\Support\Decimal::entrada($fila['cantidad_utilizada'] ?? null) }}" data-uso-cantidad data-uso-plan="{{ App\Support\Decimal::entrada($planificado) }}" data-uso-anterior="{{ $original?->cantidad_utilizada ?? '' }}" aria-describedby="uso-ayuda @error($campo.'.cantidad_utilizada') uso-{{ $indice }}-error @enderror" @error($campo.'.cantidad_utilizada') aria-invalid="true" @enderror>
            @error($campo.'.cantidad_utilizada')<span id="uso-{{ $indice }}-error" class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="col-12 col-md-2 form-group">
            <label for="uso-{{ $indice }}-diferencia"><i class="fas fa-exchange-alt field-label-icon" aria-hidden="true"></i>Diferencia</label>
            <output id="uso-{{ $indice }}-diferencia" class="form-control d-block" data-uso-diferencia>—</output>
        </div>
    </div>
    <details class="quote-options" @if(!empty($fila['observaciones_uso']) || $errors->has($campo.'.observaciones_uso')) open @endif>
        <summary>Observación del material <span class="quote-optional">Opcional</span></summary>
        <div class="form-group mt-3 mb-0">
            <label for="uso-{{ $indice }}-nota"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Observación del uso</label>
            <textarea id="uso-{{ $indice }}-nota" name="{{ $prefijo }}[observaciones_uso]" class="form-control @error($campo.'.observaciones_uso') is-invalid @enderror" maxlength="2000" rows="2">{{ $fila['observaciones_uso'] ?? '' }}</textarea>
            @error($campo.'.observaciones_uso')<span class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
        </div>
    </details>
    @if(!empty($fila['id']))
        <div class="material-retirement-actions mt-3">
            <button type="button" class="btn btn-sm btn-danger" data-uso-retirar><i class="fas fa-trash mr-2" aria-hidden="true"></i>Quitar material</button>
            <span class="text-muted small" data-uso-retirado hidden>Se guardará como no utilizado y dejará de sumar costo en la cotización al actualizarla.</span>
        </div>
    @endif
    @if(empty($fila['id']))
        <button type="button" class="btn btn-sm btn-danger mt-3" data-uso-quitar><i class="fas fa-trash mr-2" aria-hidden="true"></i>Quitar fila nueva</button>
    @endif
</div>
