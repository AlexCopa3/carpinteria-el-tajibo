
<div class="row">
    @if(\App\Support\Acceso::permite(auth()->user(), 'pedidos.programar'))
        <div class="col-12 col-md-6 form-group">
            <label for="fecha_prevista_entrega"><i class="fas fa-calendar-alt field-label-icon" aria-hidden="true"></i>Entrega prevista (opcional)</label>
            <input id="fecha_prevista_entrega" type="date" name="fecha_prevista_entrega" value="{{ old('fecha_prevista_entrega', isset($pedido) ? $pedido->fecha_prevista_entrega?->format('Y-m-d') : null) }}" min="{{ isset($pedido) ? $pedido->fecha_aceptacion->format('Y-m-d') : today()->format('Y-m-d') }}" class="form-control @error('fecha_prevista_entrega') is-invalid @enderror" aria-describedby="fecha-entrega-ayuda @error('fecha_prevista_entrega') fecha-entrega-error @enderror" @error('fecha_prevista_entrega') aria-invalid="true" @enderror>
            <small id="fecha-entrega-ayuda" class="form-text text-muted">{{ isset($pedido) ? 'No puede ser anterior a la aceptación del pedido.' : 'Elige hoy o una fecha posterior; puedes dejarla vacía.' }}</small>
            @error('fecha_prevista_entrega')<span id="fecha-entrega-error" class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
        </div>
        <div class="col-12 col-md-6 form-group"><label for="responsable_id"><i class="fas fa-user-check field-label-icon" aria-hidden="true"></i>Responsable del trabajo (opcional)</label><select id="responsable_id" name="responsable_id" class="form-control"><option value="">Sin asignar</option>@foreach($responsables as $responsable)<option value="{{ $responsable->id }}" @selected((string)old('responsable_id',$pedido->responsable_id??null)===(string)$responsable->id)>{{ $responsable->name }}</option>@endforeach</select></div>
    @endif
    <div class="col-12 form-group"><label for="observaciones"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Observaciones operativas (opcional)</label><textarea id="observaciones" name="observaciones" class="form-control" rows="2" maxlength="3000">{{ old('observaciones',$pedido->observaciones??null) }}</textarea></div>
</div>
