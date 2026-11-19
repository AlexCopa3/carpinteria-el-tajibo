<x-alerts />
<div class="payment-form-layout module-detail-layout">
<div class="card dashboard-card form-themed {{ $pago->exists ? 'form-action-update' : 'form-action-create' }}">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-wallet mr-2" aria-hidden="true"></i>{{ $pago->exists ? 'Editar pago' : 'Registrar pago recibido' }}</h2></div>
    <div class="card-body">
        <p class="text-muted">Registra el importe recibido. El sistema calcula lo pagado y el saldo; no necesitas indicar anticipo o cuota.</p>
        <form method="POST" action="{{ $pago->exists ? route('admin.pagos.update', [$pago, 'desde' => in_array(request()->query('desde'), ['pedido', 'pagos'], true) ? request()->query('desde') : null, 'volver_pedido' => request()->query('volver_pedido') === '1' ? '1' : null]) : route('admin.pagos.store', ['desde' => in_array(request()->query('desde'), ['pedido', 'pagos'], true) ? request()->query('desde') : null, 'volver_pedido' => request()->query('volver_pedido') === '1' ? '1' : null]) }}" data-cierre-pedido data-cierre-titulo="{{ $pago->exists ? '¿Actualizar el pago?' : '¿Registrar el pago?' }}" data-cierre-texto="{{ $pago->exists ? 'Se conservarán los valores anteriores y el motivo de la corrección.' : 'Confirma que el importe ya fue recibido. No se realiza ningún cobro automático.' }}">
            @csrf
            @if($pago->exists)
                @method('PUT')
                <input type="hidden" name="version" value="{{ old('version', $pago->version) }}">
            @else
                <input type="hidden" name="clave_registro" value="{{ old('clave_registro', $pago->clave_registro) }}">
            @endif
            <div class="row payment-fields">
                <div class="col-12 col-md-6 form-group">
                    <label for="pedido_id"><i class="fas fa-hammer field-label-icon" aria-hidden="true"></i>Pedido <span class="text-danger">*</span></label>
                    <select id="pedido_id" name="pedido_id" class="form-control @error('pedido_id') is-invalid @enderror" required>
                        <option value="">Selecciona un pedido</option>
                        @foreach($pedidos as $pedido)
                            <option value="{{ $pedido->id }}" @selected((string)old('pedido_id', $pago->pedido_id) === (string)$pedido->id)>Pedido {{ $pedido->id }} · {{ $pedido->datos_cliente_acordados['nombre'] }} · Bs {{ App\Support\Decimal::mostrar($pedido->precioVigente(), 2) }}</option>
                        @endforeach
                    </select>
                    @error('pedido_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="fecha"><i class="far fa-calendar-alt field-label-icon" aria-hidden="true"></i>Fecha del pago <span class="text-danger">*</span></label>
                    <input id="fecha" name="fecha" type="date" value="{{ old('fecha', $pago->fecha?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" class="form-control @error('fecha') is-invalid @enderror" required>
                    @error('fecha')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-12 col-md-4 form-group">
                    <label for="importe"><i class="fas fa-coins field-label-icon" aria-hidden="true"></i>Importe recibido (Bs) <span class="text-danger">*</span></label>
                    <input id="importe" name="importe" type="text" inputmode="decimal" value="{{ App\Support\Decimal::entrada(old('importe', $pago->importe)) }}" class="form-control @error('importe') is-invalid @enderror" required>
                    <small class="form-text text-muted">Usa punto para los decimales. Ejemplo: 250.50.</small>
                    @error('importe')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-12 col-md-4 form-group">
                    <label for="medio"><i class="fas fa-money-bill-wave field-label-icon" aria-hidden="true"></i>Medio de pago <span class="text-danger">*</span></label>
                    <select id="medio" name="medio" class="form-control @error('medio') is-invalid @enderror" required>
                        <option value="">Selecciona un medio</option>
                        @foreach(['efectivo' => 'Efectivo', 'qr' => 'QR'] as $valor => $nombre)<option value="{{ $valor }}" @selected(old('medio', $pago->medio) === $valor)>{{ $nombre }}</option>@endforeach
                    </select>
                    @error('medio')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-12 col-md-4 form-group">
                    <label for="referencia"><i class="fas fa-hashtag field-label-icon" aria-hidden="true"></i>Referencia <small class="text-muted">(opcional)</small></label>
                    <input id="referencia" name="referencia" maxlength="100" value="{{ old('referencia', $pago->referencia) }}" class="form-control @error('referencia') is-invalid @enderror">
                    @error('referencia')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-12 {{ $pago->exists ? 'col-md-6' : '' }} form-group">
                    <label for="observaciones"><i class="far fa-comment-alt field-label-icon" aria-hidden="true"></i>Observaciones <small class="text-muted">(opcional)</small></label>
                    <textarea id="observaciones" name="observaciones" rows="2" maxlength="2000" class="form-control">{{ old('observaciones', $pago->observaciones) }}</textarea>
                    @error('observaciones')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                @if($pago->exists)
                    <div class="col-12 col-md-6 form-group">
                        <label for="motivo"><i class="fas fa-pen field-label-icon" aria-hidden="true"></i>Motivo de la corrección <span class="text-danger">*</span></label>
                        <textarea id="motivo" name="motivo" rows="2" minlength="5" maxlength="1000" class="form-control" required>{{ old('motivo') }}</textarea>
                        @error('motivo')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                @endif
            </div>
            @if($pago->exists)<p class="small text-muted">El importe anterior se excluye al validar la corrección. Si cambias de pedido, se recalculan los dos saldos.</p>@endif
            <div class="form-actions module-actions">
                <a class="btn btn-cancel" href="{{ request()->query('desde') === 'pagos' && $pago->pedido ? route('admin.pagos.pedido', [$pago->pedido_id, 'desde' => request()->query('volver_pedido') === '1' ? 'pedido' : null]) : (request()->query('desde') === 'pedido' && $pago->pedido && auth()->user()->can('view', $pago->pedido) ? route('admin.pedidos.show', $pago->pedido) : route('admin.pagos.index')) }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                <button type="submit" class="btn {{ $pago->exists ? 'btn-success' : 'btn-primary' }}"><i class="fas fa-save mr-2" aria-hidden="true"></i>{{ $pago->exists ? 'Actualizar pago' : 'Registrar pago' }}</button>
            </div>
        </form>
    </div>
</div>
</div>
