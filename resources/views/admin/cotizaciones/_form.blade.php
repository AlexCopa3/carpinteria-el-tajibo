@php
    $filas = old('detalles', $cotizacion->exists ? $cotizacion->detalles->toArray() : [[]]);
@endphp
<x-alerts />
@if($materiales->isEmpty())
    <div class="alert alert-warning">Registra primero los materiales desde su módulo.</div>
@endif
@if($principales->isEmpty())
    <div class="alert alert-info">No hay materiales principales disponibles. Registra MDF, Melamina o Madera en Materiales, dentro de la categoría «Material principal», cuando tengas sus unidades y costos.</div>
@endif
<form @if($cotizacion->pedido) data-pedido-cotizacion @endif method="POST" action="{{ $cotizacion->exists ? route('admin.cotizaciones.update', [$cotizacion, 'desde' => request()->query('desde') === 'pedido' ? 'pedido' : null]) : route('admin.cotizaciones.store') }}" data-cotizacion-form data-similitud-url="{{ $cotizacion->exists ? route('admin.similitud.buscarEditar', $cotizacion) : route('admin.similitud.buscar') }}" class="quote-guided-form">
    @csrf
    @if($cotizacion->exists)
        @method('PUT')
        <input type="hidden" name="version" value="{{ old('version', $cotizacion->version) }}">
    @else
        <input type="hidden" name="clave_registro" value="{{ old('clave_registro', $cotizacion->clave_registro) }}">
    @endif
    <div class="quote-welcome mb-3">
        <div class="quote-welcome-icon"><i class="fas fa-pencil-ruler" aria-hidden="true"></i></div>
        <div>
            <h2>Prepara tu cotización</h2>
            <p>Describe un mueble y busca antecedentes. Elige una propuesta para revisarla o continúa manualmente.</p>
            <ol class="quote-guide" aria-label="Orden de llenado">
                <li><span>1</span> Describe el mueble</li>
                <li><span>2</span> Busca casos similares</li>
                <li><span>3</span> Elige y revisa, o llena manualmente</li>
            </ol>
            <p class="quote-form-tip mt-3"><i class="far fa-lightbulb mr-2" aria-hidden="true"></i>Medidas en centímetros e importes en Bs. Usa punto para los decimales. <span class="text-danger">*</span> indica un campo obligatorio.</p>
            @if(!$cotizacion->pedido)<p class="text-muted small mb-0">No necesitas los datos del cliente para cotizar; se pedirán cuando acepte el pedido.</p>@endif
        </div>
    </div>
    <div data-detalles>
        @foreach($filas as $indice => $datos)
            @include('admin.cotizaciones._detalle')
        @endforeach
    </div>
    <div class="quote-add-furniture mb-3">
        <div><strong>¿La cotización incluye otro mueble?</strong><p>Añádelo por separado si tiene otras características o costos.</p></div>
        <button class="btn btn-primary" type="button" data-agregar-detalle><i class="fas fa-plus mr-2" aria-hidden="true"></i>Añadir mueble</button>
    </div>
    <div class="card dashboard-card quote-review">
        <div class="card-body">
            <div class="quote-step-heading"><span class="quote-step-number"><i class="fas fa-check" aria-hidden="true"></i></span><div><h2>Revisa y guarda</h2><p>Este es el costo estimado de todos los muebles de la cotización.</p></div></div>
            <div class="quote-review-grid">
                <div class="quote-review-total"><span>Costo total estimado</span><strong data-total-cotizacion aria-live="polite">—</strong><small>Materiales + mano de obra + otros costos.</small></div>
                @if(App\Support\Acceso::permite(auth()->user(), 'margenes.gestionar'))
                    <div class="form-group mb-0">
                        <label for="recargo_porcentaje"><i class="fas fa-percentage field-label-icon" aria-hidden="true"></i>Ganancia sobre el costo (%)</label>
                        <input id="recargo_porcentaje" name="recargo_porcentaje" value="{{ App\Support\Decimal::entrada(old('recargo_porcentaje', $cotizacion->recargo_porcentaje)) }}" class="form-control" inputmode="decimal" data-recargo>
                        <small class="form-text text-muted">Vacío permite definir el precio manualmente.</small>
                        @error('recargo_porcentaje')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                    </div>
                @endif
                @if(App\Support\Acceso::permite(auth()->user(), 'margenes.consultar'))
                    <div class="quote-review-price"><span>Precio sugerido</span><strong data-sugerido-cotizacion aria-live="polite">—</strong><small>El precio final se aprueba después de guardar.</small></div>
                @endif
            </div>
            <p class="quote-form-tip mt-3"><i class="fas fa-info-circle mr-2" aria-hidden="true"></i>Puedes guardar con costos pendientes para completarlos después. El precio final requiere aprobación.</p>
            <div class="form-actions">
                <a class="btn btn-cancel" href="{{ request()->query('desde') === 'pedido' && $cotizacion->pedido && auth()->user()->can('view', $cotizacion->pedido) ? route('admin.pedidos.show', $cotizacion->pedido) : route('admin.cotizaciones.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                <button class="btn {{ $cotizacion->exists ? 'btn-success' : 'btn-primary' }}" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>{{ $cotizacion->exists ? 'Actualizar cotización' : 'Registrar cotización' }}</button>
            </div>
        </div>
    </div>
</form>
<template id="plantilla-detalle">
    @include('admin.cotizaciones._detalle', ['indice' => '__D__', 'datos' => []])
</template>
<template id="plantilla-material">
    @include('admin.cotizaciones._material', ['indice' => '__D__', 'indiceMaterial' => '__M__', 'datos' => []])
</template>
