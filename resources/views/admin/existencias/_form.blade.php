    <section class="card dashboard-card form-panel form-themed {{ $corregir ? 'form-action-update' : 'form-action-create' }}">
        <div class="card-header"><h2>{{ $corregir ? 'Actualizar existencia disponible' : 'Registrar cantidad recibida' }}</h2></div>
        <div class="card-body">
            <p>Existencia actual: <strong>{{ App\Support\Decimal::mostrar($material->existencia?->cantidad ?? '0') }} {{ $material->unidad->nombre }}</strong>.</p>
            <p class="text-muted">{{ $corregir ? 'Indica el total que realmente tienes. El sistema registrará la diferencia.' : 'Indica cuánto recibiste. Esa cantidad se sumará a la existencia actual.' }} El costo de referencia no cambia.</p>
            <form method="POST" action="{{ route($corregir ? 'admin.existencias.update' : 'admin.existencias.store', $material) }}">
                @csrf
                @if($corregir) @method('PUT') @endif
                <input type="hidden" name="clave" value="{{ old('clave', $clave) }}">
                <input type="hidden" name="unidad_id" value="{{ old('unidad_id', $material->unidad_id) }}">
                <input type="hidden" name="existencia_esperada" value="{{ old('existencia_esperada', $material->existencia?->cantidad ?? '0') }}">
                @error('clave')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                @error('unidad_id')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                @error('existencia_esperada')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                <div class="row form-fields">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="cantidad"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>{{ $corregir ? 'Cantidad total disponible' : 'Cantidad que recibiste' }} ({{ $material->unidad->nombre }}) <span class="text-danger">*</span></label>
                            <input id="cantidad" name="cantidad" type="number" class="form-control @error('cantidad') is-invalid @enderror" value="{{ old('cantidad', $corregir ? ($material->existencia?->cantidad ?? '0') : '') }}" min="{{ $corregir ? '0' : ($material->unidad->fraccionaria ? '0.0001' : '1') }}" step="{{ $material->unidad->fraccionaria ? '0.0001' : '1' }}" required aria-describedby="cantidad-help @error('cantidad') cantidad-error @enderror" @error('cantidad') aria-invalid="true" @enderror>
                            <small id="cantidad-help" class="form-text text-muted">{{ $material->unidad->fraccionaria ? 'Admite hasta cuatro decimales.' : 'Introduce una cantidad entera.' }}</small>
                            @error('cantidad')<span id="cantidad-error" class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="motivo"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Motivo <span class="text-danger">*</span></label>
                            <input id="motivo" name="motivo" class="form-control @error('motivo') is-invalid @enderror" value="{{ old('motivo') }}" minlength="5" maxlength="2000" required placeholder="{{ $corregir ? 'Ej.: recuento de materiales en el taller' : 'Ej.: material recibido para el taller' }}" @error('motivo') aria-invalid="true" aria-describedby="motivo-error" @enderror>
                            @error('motivo')<span id="motivo-error" class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a class="btn btn-default btn-cancel" href="{{ route('admin.existencias.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                    <button class="btn {{ $corregir ? 'btn-success' : 'btn-primary' }}" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>{{ $corregir ? 'Editar cantidad' : 'Agregar cantidad' }}</button>
                </div>
            </form>
        </div>
    </section>
