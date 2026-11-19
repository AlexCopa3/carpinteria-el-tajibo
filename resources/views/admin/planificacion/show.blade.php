@extends('adminlte::page')
@section('title', 'Materiales del pedido')
@section('content_header')
    <x-page-heading :refined="true" title="Materiales del pedido" eyebrow="OPERACIONES / PLANIFICACIÓN" subtitle="Consulta lo necesario para preparar el trabajo." />
@stop
@section('content')
    <x-alerts />

    <div class="module-detail-layout planning-detail-layout">
        <section class="card dashboard-card planning-order-card">
            <div class="card-body">
                <div class="order-summary-header mb-3 pb-3">
                    <div class="order-summary-identity">
                        <span class="order-summary-icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                        <div>
                            <div class="order-summary-title">
                                <h2>Pedido {{ $pedido->id }}</h2>
                                <x-estado-pedido :pedido="$pedido" />
                            </div>
                            <p>Materiales previstos para este trabajo.</p>
                        </div>
                    </div>
                    <div class="module-actions">
                        <a class="btn btn-cancel" href="{{ request('desde') === 'pedido' && auth()->user()->can('view', $pedido) ? route('admin.pedidos.show', $pedido) : route('admin.planificacion.index') }}">
                            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>{{ request('desde') === 'pedido' && auth()->user()->can('view', $pedido) ? 'Volver al pedido' : 'Volver al listado' }}
                        </a>
                        @if(request('desde') !== 'pedido')
                            @can('view', $pedido)
                                <a class="btn btn-info" href="{{ route('admin.pedidos.show', $pedido) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver pedido</a>
                            @endcan
                        @endif
                    </div>
                </div>
                <dl class="record-fields planning-order-fields mb-0">
                    <div class="record-field">
                        <dt><i class="fas fa-user record-value-icon" aria-hidden="true"></i>Cliente</dt>
                        <dd>{{ $pedido->datos_cliente_acordados['nombre'] }}</dd>
                    </div>
                    <div class="record-field">
                        <dt><i class="fas fa-calendar-alt record-value-icon" aria-hidden="true"></i>Entrega prevista</dt>
                        <dd>{{ $pedido->fecha_prevista_entrega?->format('d/m/Y') ?? 'Sin definir' }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <div class="planning-overview">
            <section class="card dashboard-card record-card planning-requirements">
                <x-ficha-heading title="Materiales necesarios" subtitle="Cantidades totales para todos los muebles del pedido." icon="fas fa-cubes" />
                <div class="card-body">
                    <div class="table-responsive" tabindex="0" role="region" aria-label="Necesidades y existencias de materiales">
                        <table class="table table-hover tajibo-table planning-summary-table mb-0">
                            <thead>
                                <tr><th>Material / unidad</th><th class="text-right">Necesario</th><th class="text-right">Disponible</th><th class="text-right">Por comprar</th></tr>
                            </thead>
                            <tbody>
                                @forelse($disponibilidad as $material)
                                    <tr>
                                        <td><strong class="categoria-nombre">{{ $material['nombre'] }}</strong><small class="d-block text-muted">{{ $material['unidad'] }}</small></td>
                                        <td class="text-right planning-number">{{ App\Support\Decimal::mostrar($material['necesaria']) }}</td>
                                        <td class="text-right planning-number">{{ App\Support\Decimal::mostrar($material['existencia']) }}</td>
                                        <td class="text-right"><span class="record-status {{ $material['comprar']->isPositive() ? 'is-inactive' : 'is-active' }}"><i class="fas {{ $material['comprar']->isPositive() ? 'fa-shopping-basket' : 'fa-check-circle' }} mr-1" aria-hidden="true"></i>{{ App\Support\Decimal::mostrar($material['comprar']) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">Este pedido todavía no tiene materiales planificados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="planning-reference mb-0"><i class="fas fa-info-circle" aria-hidden="true"></i><span>Existencias registradas como referencia. No hay reservas, descuentos ni compras automáticas; otros pedidos pueden necesitar el mismo material.</span></p>
                </div>
            </section>

            <aside class="card dashboard-card record-card planning-guide">
                <x-ficha-heading title="¿Qué necesitas hacer?" subtitle="Cada acción tiene su lugar." icon="fas fa-compass" />
                <div class="card-body">
                    <div class="planning-guide-item">
                        <span class="planning-guide-icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                        <div><h3>Cambiar lo previsto</h3><p>Entra al pedido y utiliza <strong>Editar muebles y precios</strong> para modificar materiales y revisar el presupuesto.</p></div>
                    </div>
                    <div class="planning-guide-item">
                        <span class="planning-guide-icon is-consumption" aria-hidden="true"><i class="fas fa-tools"></i></span>
                        <div><h3>Anotar lo utilizado</h3><p>Durante la fabricación, utiliza <strong>Registrar materiales utilizados</strong> para indicar el consumo.</p></div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="planning-section-label"><i class="fas fa-chair" aria-hidden="true"></i><h2>Distribución por mueble</h2></div>
        <div class="planning-furniture-grid planning-furniture-cards">
            @foreach($pedido->detalles as $detalle)
                <section class="card dashboard-card record-card">
                    <x-ficha-heading :title="'Mueble '.$detalle->posicion.' · '.$detalle->detalleCotizacion->tipo_nombre" :subtitle="$detalle->cantidad_muebles.' '.($detalle->cantidad_muebles === 1 ? 'unidad' : 'unidades').' · Cantidades planificadas'" icon="fas fa-chair" />
                    <div class="card-body">
                        <div class="table-responsive" tabindex="0" role="region" aria-label="Materiales planificados del mueble {{ $detalle->posicion }}">
                            <table class="table tajibo-table mb-0">
                                <thead><tr><th>Material / unidad</th><th>Cantidad inicial</th><th>Cantidad actual</th><th>Observaciones</th></tr></thead>
                                <tbody>
                                    @forelse($detalle->materiales as $fila)
                                        <tr>
                                            <td><strong class="categoria-nombre">{{ $fila->material_nombre }}</strong><small class="d-block text-muted">{{ $fila->unidad_nombre }}</small>@if(!$fila->material->activo)<span class="badge badge-secondary">Material inactivo</span>@endif</td>
                                            <td class="planning-number">{{ $fila->cantidad_inicial === null ? 'Añadido después' : App\Support\Decimal::mostrar($fila->cantidad_inicial) }}</td>
                                            <td class="planning-number">{{ App\Support\Decimal::mostrar($fila->cantidad_planificada) }}</td>
                                            <td>{{ $fila->observaciones ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted">No hay materiales planificados para este mueble.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        @include('admin.fabricacion._resumen')
    </div>
@stop
