@php($pedidoCotizacion = $cotizacion->pedido)
@if(in_array($pedidoCotizacion->estado, ['aceptado', 'en_fabricacion'], true))
<section class="card dashboard-card record-card mb-3">
    <x-ficha-heading title="Confirmar el precio del pedido" subtitle="Los pagos y el importe aceptado se mantienen hasta confirmar." icon="fas fa-check-circle" />
    <div class="card-body">
        @if($cotizacion->aprobado_at)
            <p>Total del pedido: <strong>Bs {{ App\Support\Decimal::mostrar($pedidoCotizacion->precio_total_acordado, 2) }}</strong> · Precio aprobado: <strong>Bs {{ App\Support\Decimal::mostrar($cotizacion->precio_final, 2) }}</strong></p>
            @can('aceptarCotizacion', $pedidoCotizacion)
                <form method="POST" action="{{ route('admin.pedidos.cotizacion.aceptar', $pedidoCotizacion) }}" data-aceptar-cotizacion>
                    @csrf
                    <input type="hidden" name="version" value="{{ $pedidoCotizacion->version }}">
                    <input type="hidden" name="cotizacion_version" value="{{ $cotizacion->version }}">
                    <div class="custom-control custom-checkbox mb-3">
                        <input id="aceptacion-cotizacion" type="checkbox" class="custom-control-input" name="aceptacion" value="1" required>
                        <label class="custom-control-label" for="aceptacion-cotizacion">Confirmo que el cliente aceptó los muebles, cantidades y precio de la cotización</label>
                    </div>
                    <button class="btn btn-success" type="submit"><i class="fas fa-check-circle mr-2" aria-hidden="true"></i>Actualizar pedido</button>
                </form>
            @endcan
        @else
            <p class="mb-0">Completa los costos y aprueba el precio para actualizar el importe del pedido.</p>
        @endif
    </div>
</section>
@endif
