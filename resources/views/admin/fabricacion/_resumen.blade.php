@can('verUsoMateriales', $pedido)
    <section class="card dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-tools mr-2" aria-hidden="true"></i>Materiales utilizados</h2>
            @can('registrarUsoMateriales', $pedido)
                <a class="btn btn-success" href="{{ route('admin.fabricacion.edit', [$pedido, 'desde' => request()->routeIs('admin.planificacion.show') ? 'planificacion' : 'pedido', 'volver_pedido' => request()->routeIs('admin.planificacion.show') && request('desde') === 'pedido' ? '1' : null]) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Registrar materiales utilizados</a>
            @endcan
        </div>
        <div class="card-body">
            @if(request()->routeIs('admin.pedidos.show'))
                <p class="text-muted small">Cantidades totales por grupo de muebles. Vacío significa pendiente.</p>
            @else
                <p class="text-muted small">Cantidades totales por grupo de muebles. Diferencia = utilizado − planificado. Los valores pendientes no se consideran cero ni modifican existencias.</p>
            @endif
            @foreach($pedido->detalles as $detalle)
                <h3 class="h6">Mueble {{ $detalle->posicion }} · {{ $detalle->detalleCotizacion->tipo_nombre }}</h3>
                <div class="table-responsive mb-4" tabindex="0" role="region" aria-label="Materiales utilizados del mueble {{ $detalle->posicion }}">
                    <table class="table table-hover tajibo-table">
                        <thead><tr><th>Material / unidad</th><th>Planificado</th><th>Utilizado</th><th>Diferencia</th>@if(!request()->routeIs('admin.pedidos.show'))<th>Último registro</th>@endif</tr></thead>
                        <tbody>
                            @forelse($detalle->materiales as $fila)
                                <tr>
                                    <td>{{ $fila->material_nombre }}<small class="d-block text-muted">{{ $fila->unidad_nombre }}@if($fila->componente !== 'General') · {{ $fila->componente }}@endif</small>@if($fila->observaciones_uso)<small class="d-block quote-text">{{ $fila->observaciones_uso }}</small>@endif</td>
                                    <td>{{ App\Support\Decimal::mostrar($fila->cantidad_planificada) }}</td>
                                    <td>@if($fila->cantidad_utilizada === null)<span class="record-status is-inactive">Sin registrar</span>@else{{ App\Support\Decimal::mostrar($fila->cantidad_utilizada) }}@if(Brick\Math\BigDecimal::of($fila->cantidad_utilizada)->isZero())<small class="d-block text-muted">No utilizado</small>@endif @endif</td>
                                    <td>{{ $fila->diferencia_utilizada === null ? '—' : App\Support\Decimal::mostrar($fila->diferencia_utilizada) }}</td>
                                    @if(!request()->routeIs('admin.pedidos.show'))
                                    <td>@if($fila->uso_registrado_at){{ $fila->usoRegistradoPor?->name ?? 'Sin responsable registrado' }}<small class="d-block text-muted">{{ $fila->uso_registrado_at->format('d/m/Y H:i') }}</small>@else — @endif</td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ request()->routeIs('admin.pedidos.show') ? 4 : 5 }}">No hay materiales registrados para este mueble.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
            @if(!request()->routeIs('admin.pedidos.show'))
                <p class="small text-muted mb-0">Las cantidades utilizadas se conservan separadas de la planificación. Consulta su validación en la revisión final del pedido.</p>
            @endif
        </div>
    </section>
@endcan
