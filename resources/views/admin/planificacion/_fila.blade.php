<div class="row planning-material-row" data-plan-fila>
    <input type="hidden" name="materiales[{{ $indice }}][detalle_pedido_id]" value="{{ $fila['detalle_pedido_id'] }}">
    @if(!empty($fila['id']))
        <input type="hidden" name="materiales[{{ $indice }}][id]" value="{{ $fila['id'] }}">
    @endif
    <div class="col-12 col-md-5 form-group">
        <label for="plan-{{ $indice }}-material"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Material</label>
        @if(!empty($fila['id']))
            <input type="hidden" name="materiales[{{ $indice }}][material_id]" value="{{ $fila['material_id'] }}">
            <input id="plan-{{ $indice }}-material" class="form-control" readonly value="{{ $fila['material_nombre'] ?? App\Models\Material::find($fila['material_id'])?->nombre }}">
            <small class="form-text text-muted">{{ $fila['unidad_nombre'] ?? '' }}</small>
        @else
            <select id="plan-{{ $indice }}-material" name="materiales[{{ $indice }}][material_id]" class="form-control" required>
                <option value="">Selecciona un material</option>
                @foreach($materiales as $material)
                    <option value="{{ $material->id }}" @selected((string)($fila['material_id'] ?? '') === (string)$material->id)>{{ $material->nombre }} · {{ $material->unidad->nombre }}</option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-12 col-md-3 form-group">
        <label for="plan-{{ $indice }}-cantidad"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Cantidad total <span class="text-danger">*</span></label>
        <input id="plan-{{ $indice }}-cantidad" name="materiales[{{ $indice }}][cantidad_planificada]" value="{{ App\Support\Decimal::entrada($fila['cantidad_planificada'] ?? '') }}" class="form-control" required inputmode="decimal">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="plan-{{ $indice }}-observaciones"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Observaciones</label>
        <input id="plan-{{ $indice }}-observaciones" name="materiales[{{ $indice }}][observaciones]" value="{{ $fila['observaciones'] ?? '' }}" class="form-control" maxlength="2000">
    </div>
    @if(empty($fila['id']))
        <div class="col-12 mb-3"><button type="button" class="btn btn-sm btn-danger" data-plan-quitar><i class="fas fa-trash mr-2" aria-hidden="true"></i>Quitar fila nueva</button></div>
    @endif
</div>
