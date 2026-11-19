@extends('adminlte::page')
@section('title', 'Materiales utilizados')
@section('content_header')
    <x-page-heading :refined="true" title="Registrar materiales utilizados" eyebrow="PEDIDOS / FABRICACIÓN" subtitle="Revisa lo que utilizaste; no necesitas volver a llenar la cotización." />
@stop
@section('content')
    <x-alerts />
    <div class="row"><div class="col-12 col-xl-10 uso-materiales-layout">
    @php
        $filasOriginales = $pedido->detalles->flatMap->materiales->keyBy('id');
        $filas = old('materiales', $filasOriginales->values()->map(fn($fila) => $fila->only(['id', 'detalle_pedido_id', 'material_id', 'cantidad_utilizada', 'observaciones_uso']))->all());
    @endphp
    <div class="uso-intro-grid">
        <section class="card dashboard-card record-card">
            <x-ficha-heading :title="'Pedido '.$pedido->id" subtitle="Registro de materiales utilizados en fabricación." icon="fas fa-hammer" />
            <div class="card-body">
                <x-estado-pedido :pedido="$pedido" />
                <p class="mt-3 mb-0" id="uso-ayuda">Escribe la cantidad total utilizada por cada grupo de muebles. Vacío significa sin registrar; 0, no utilizado. Usa punto para los decimales.</p>
            </div>
        </section>
        <section class="card dashboard-card record-card">
            <x-ficha-heading title="Antes de guardar" subtitle="Revisa las cantidades de cada material." icon="fas fa-clipboard-check" />
            <div class="card-body">
                <p class="mb-0 text-muted">Al añadir, cambiar o quitar materiales, se actualiza la misma cotización y se recalculan sus costos, según tus permisos. Quitar marca el material como no utilizado y conserva el registro. El precio acordado solo cambia después de aprobar la cotización y confirmar la aceptación del cliente. Guardar no descuenta existencias.</p>
            </div>
        </section>
    </div>
    <form method="POST" action="{{ route('admin.fabricacion.update', [$pedido, 'desde' => in_array(request()->query('desde'), ['pedido', 'planificacion'], true) ? request()->query('desde') : null, 'volver_pedido' => request()->query('volver_pedido') === '1' ? '1' : null]) }}" data-materiales-utilizados>
        @csrf
        @method('PUT')
        <input type="hidden" name="version" value="{{ old('version', $pedido->version) }}">
        @foreach($pedido->detalles as $detalle)
            <section class="card dashboard-card form-themed form-action-update" data-uso-detalle="{{ $detalle->id }}">
                <div class="card-header"><h2 class="card-title"><i class="fas fa-chair mr-2" aria-hidden="true"></i>Mueble {{ $detalle->posicion }} · {{ $detalle->detalleCotizacion->tipo_nombre }} · {{ $detalle->cantidad_muebles }} {{ $detalle->cantidad_muebles === 1 ? 'unidad' : 'unidades' }}</h2></div>
                <div class="card-body">
                    <div data-uso-filas>
                        @foreach($filas as $indice => $fila)
                            @if((int)($fila['detalle_pedido_id'] ?? 0) === $detalle->id)
                                @include('admin.fabricacion._fila', ['indice' => $indice, 'fila' => $fila])
                            @endif
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-primary" data-uso-agregar><i class="fas fa-plus mr-2" aria-hidden="true"></i>Añadir material utilizado</button>
                </div>
            </section>
        @endforeach
        <section class="card dashboard-card record-card uso-save-panel">
            <x-ficha-heading title="Guardar el registro" subtitle="Si corriges un valor anterior, indica el motivo." icon="fas fa-save" />
            <div class="card-body">
                <div class="form-group">
                    <label for="motivo_uso"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Motivo de la corrección <span class="text-danger" data-uso-motivo-marca hidden>*</span></label>
                    <textarea id="motivo_uso" name="motivo_correccion" class="form-control @error('motivo_correccion') is-invalid @enderror" maxlength="2000" rows="2" data-uso-motivo aria-describedby="motivo-uso-ayuda">{{ old('motivo_correccion') }}</textarea>
                    <small id="motivo-uso-ayuda" class="form-text text-muted">Solo es obligatorio al cambiar una cantidad que ya estaba registrada: explica el motivo con al menos 5 caracteres.</small>
                    @error('motivo_correccion')<span class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-actions uso-actions">
                    <a class="btn btn-cancel" href="{{ request()->query('desde') === 'planificacion' && (new App\Policies\PlanificacionMaterialPolicy)->view(auth()->user(), $pedido) ? route('admin.planificacion.show', [$pedido, 'desde' => request()->query('volver_pedido') === '1' ? 'pedido' : null]) : (auth()->user()->can('view', $pedido) ? route('admin.pedidos.show', $pedido) : route('admin.planificacion.index')) }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-2" aria-hidden="true"></i>Guardar materiales</button>
                </div>
            </div>
        </section>
    </form>
    </div></div>
    <template id="uso-fila-nueva">
        @include('admin.fabricacion._fila', ['indice' => '__I__', 'fila' => ['detalle_pedido_id' => '__D__']])
    </template>
@stop
