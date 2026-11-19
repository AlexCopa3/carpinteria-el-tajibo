@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" :title="'Hola, '.auth()->user()->name" eyebrow="PANEL PRINCIPAL" />
@stop

@section('content')
    <x-alerts />
    <div class="workshop-dashboard workshop-reference">
        <section class="workshop-welcome" aria-labelledby="taller-bienvenida">
            <div class="workshop-welcome-content">
                <p class="workshop-overline"><span></span>EL TAJIBO · CARPINTERÍA</p>
                <h2 id="taller-bienvenida">Todo listo para<br><em>seguir creando.</em></h2>
                <p class="workshop-welcome-copy">Tus cotizaciones y trabajos, organizados para avanzar con claridad.</p>
                <div class="workshop-welcome-actions">
                    @can('create', App\Models\Cotizacion::class)
                        <a class="btn workshop-primary-action" href="{{ route('admin.cotizaciones.create') }}"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Nueva cotización</a>
                    @endcan
                </div>
            </div>
            <div class="workshop-art" aria-hidden="true">
                <div class="workshop-art-frame"><div class="workshop-art-cabinet"><span></span><span></span><span></span><span></span></div></div>
                <span class="workshop-art-label">IDEAS QUE TOMAN FORMA</span>
            </div>
        </section>

        <div class="workshop-metrics">
            @isset($indicadores['cotizaciones'])
                <a class="workshop-metric is-blue" href="{{ route('admin.cotizaciones.index') }}">
                    <span class="workshop-metric-icon"><i class="fas fa-file-invoice" aria-hidden="true"></i></span>
                    <span class="workshop-metric-label">Cotizaciones del mes</span>
                    <strong>{{ $indicadores['cotizaciones'] }}</strong>
                    <span class="workshop-metric-note">{{ now()->format('m/Y') }} · por fecha de cotización</span>
                </a>
                <a class="workshop-metric is-plum" href="{{ route('admin.cotizaciones.index') }}">
                    <span class="workshop-metric-icon"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
                    <span class="workshop-metric-label">Cotizaciones por revisar</span>
                    <strong>{{ $indicadores['cotizaciones_pendientes'] }}</strong>
                    <span class="workshop-metric-note">Borradores y costos pendientes</span>
                </a>
            @endisset
            @isset($indicadores['clientes'])
                <a class="workshop-metric is-teal" href="{{ route('admin.clientes.index') }}">
                    <span class="workshop-metric-icon"><i class="fas fa-user-friends" aria-hidden="true"></i></span>
                    <span class="workshop-metric-label">Clientes registrados</span>
                    <strong>{{ $indicadores['clientes'] }}</strong>
                    <span class="workshop-metric-note">Datos de contacto del sistema</span>
                </a>
            @endisset
            @isset($indicadores['materiales'])
                <a class="workshop-metric is-rose" href="{{ route('admin.materiales.index') }}">
                    <span class="workshop-metric-icon"><i class="fas fa-cubes" aria-hidden="true"></i></span>
                    <span class="workshop-metric-label">Materiales activos</span>
                    <strong>{{ $indicadores['materiales'] }}</strong>
                    <span class="workshop-metric-note">Disponibles en el catálogo</span>
                </a>
            @endisset
        </div>

        <div class="workshop-main-grid">
            @isset($indicadores['pedidos_aceptados'])
                @php
                    $totalPedidos = array_sum(array_column($tableroPedidos, 'total'));
                    $segmentos = [
                        'aceptado' => ['nombre' => 'Aceptado', 'color' => 'var(--order-accepted)'],
                        'en_fabricacion' => ['nombre' => 'En fabricación', 'color' => 'var(--order-working)'],
                        'terminado' => ['nombre' => 'Terminado', 'color' => 'var(--order-finished)'],
                        'entregado' => ['nombre' => 'Entregado', 'color' => 'var(--order-delivered)'],
                    ];
                    $acumulado = 0;
                @endphp
                <section class="card dashboard-card workshop-distribution" aria-labelledby="distribucion-titulo">
                    <div class="workshop-section-heading">
                        <div class="workshop-section-title"><span class="workshop-section-icon"><i class="fas fa-chart-pie" aria-hidden="true"></i></span><div><p>VISIÓN GENERAL</p><h2 id="distribucion-titulo">Estado de los pedidos</h2></div></div>
                        <span class="workshop-period">Todos los registros</span>
                    </div>
                    <div class="workshop-chart-body">
                        <div class="workshop-donut">
                            <svg viewBox="0 0 42 42" role="img" aria-label="Distribución de {{ $totalPedidos }} pedidos por estado. Detalle en la lista siguiente.">
                                <circle cx="21" cy="21" r="15.9154943092" fill="none" stroke="var(--border)" stroke-width="4.5" />
                                @foreach($segmentos as $estado => $segmento)
                                    @php($porcentaje = $totalPedidos > 0 ? $tableroPedidos[$estado]['total'] / $totalPedidos * 100 : 0)
                                    @if($porcentaje > 0)
                                        <circle cx="21" cy="21" r="15.9154943092" fill="none" stroke="{{ $segmento['color'] }}" stroke-width="4.5" stroke-dasharray="{{ $porcentaje }} {{ 100 - $porcentaje }}" stroke-dashoffset="{{ 25 - $acumulado }}" />
                                    @endif
                                    @php($acumulado += $porcentaje)
                                @endforeach
                            </svg>
                            <div class="workshop-donut-label" aria-hidden="true"><strong>{{ $totalPedidos }}</strong><span>pedidos</span></div>
                        </div>
                        <ul class="workshop-chart-legend">
                            @foreach($segmentos as $estado => $segmento)
                                <li style="--legend-color: {{ $segmento['color'] }}">
                                    <a href="{{ route('admin.pedidos.index', ['estado' => $estado]) }}">
                                        <span class="workshop-legend-dot" style="background: {{ $segmento['color'] }}" aria-hidden="true"></span>
                                        <span>{{ $segmento['nombre'] }}</span>
                                        <strong>{{ $totalPedidos > 0 ? str_replace('.', ',', (string) round($tableroPedidos[$estado]['total'] / $totalPedidos * 100, 1)) : '0' }} %</strong>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <p class="workshop-panel-note">{{ $totalPedidos > 0 ? 'Proporción de cada estado sobre el total de pedidos.' : 'Cuando registres pedidos, verás su distribución aquí.' }}</p>
                </section>
            @endisset
            @isset($indicadores['cotizaciones'])
                <section class="card dashboard-card workshop-quotes" aria-labelledby="cotizaciones-recientes-titulo">
                    <div class="workshop-section-heading">
                        <div class="workshop-section-title"><span class="workshop-section-icon"><i class="fas fa-file-invoice" aria-hidden="true"></i></span><div><p>ÚLTIMOS PRESUPUESTOS</p><h2 id="cotizaciones-recientes-titulo">Cotizaciones recientes</h2></div></div>
                        <a class="workshop-text-link" href="{{ route('admin.cotizaciones.index') }}">Ver todas<i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="table-responsive" tabindex="0" role="region" aria-label="Cotizaciones recientes">
                        <table class="table table-hover workshop-quotes-table mb-0">
                            <thead><tr><th scope="col">ID</th><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col">Acción</th></tr></thead>
                            <tbody>
                                @forelse($cotizacionesRecientes as $cotizacion)
                                    <tr>
                                        <td><strong class="categoria-nombre">{{ $cotizacion->id }}</strong></td>
                                        <td>{{ $cotizacion->fecha->format('d/m/Y') }}</td>
                                        <td><span class="record-status {{ $cotizacion->estado === 'aprobada' ? 'is-active' : 'is-inactive' }}">{{ $cotizacion->estado_nombre }}</span></td>
                                        <td><a class="btn btn-sm btn-info" href="{{ route('admin.cotizaciones.show', $cotizacion) }}" aria-label="Ver cotización {{ $cotizacion->id }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><x-empty-state icon="fas fa-file-invoice" title="Aún no hay cotizaciones" description="Los presupuestos registrados aparecerán aquí." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($cotizacionesRecientes->isNotEmpty())
                        <p class="workshop-panel-note">Hasta 5 registros recientes, ordenados por fecha de cotización.</p>
                    @endif
                </section>
            @endisset
            @isset($indicadores['pedidos_aceptados'])
                <section class="card dashboard-card workshop-orders" aria-labelledby="tablero-pedidos-titulo">
                    <div class="workshop-section-heading">
                        <div class="workshop-section-title"><span class="workshop-section-icon"><i class="fas fa-columns" aria-hidden="true"></i></span><div><p>EL TRABAJO, PASO A PASO</p><h2 id="tablero-pedidos-titulo">Tablero de pedidos</h2></div></div>
                        <a class="workshop-text-link" href="{{ route('admin.pedidos.index') }}">Ver todos<i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="workshop-board">
                        @foreach([
                            'aceptado' => ['nombre' => 'Aceptado', 'icono' => 'far fa-clock', 'ayuda' => 'Por iniciar'],
                            'en_fabricacion' => ['nombre' => 'En fabricación', 'icono' => 'fas fa-hammer', 'ayuda' => 'En el taller'],
                            'terminado' => ['nombre' => 'Terminado', 'icono' => 'fas fa-check-circle', 'ayuda' => 'Listo para entregar'],
                            'entregado' => ['nombre' => 'Entregado', 'icono' => 'fas fa-truck', 'ayuda' => 'Trabajo completado'],
                        ] as $estado => $columna)
                            <section class="workshop-board-column board-{{ $estado }}" aria-labelledby="tablero-{{ $estado }}">
                                <div class="workshop-board-heading">
                                    <div><h3 id="tablero-{{ $estado }}"><i class="{{ $columna['icono'] }}" aria-hidden="true"></i>{{ $columna['nombre'] }}</h3><p>{{ $columna['ayuda'] }}</p></div>
                                    <span class="workshop-board-count" aria-label="{{ $tableroPedidos[$estado]['total'] }} pedidos">{{ $tableroPedidos[$estado]['total'] }}</span>
                                </div>
                                <div class="workshop-board-cards">
                                    @forelse($tableroPedidos[$estado]['pedidos'] as $pedido)
                                        <article class="workshop-order-card">
                                            <h4>Pedido {{ $pedido->id }}</h4>
                                            <span class="workshop-order-date" aria-label="Entrega prevista: {{ $pedido->fecha_prevista_entrega?->format('d/m/Y') ?? 'Sin definir' }}">{{ $pedido->fecha_prevista_entrega?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                            <a class="workshop-order-link" href="{{ route('admin.pedidos.show', $pedido) }}" aria-label="Ver pedido {{ $pedido->id }}"><i class="fas fa-eye" aria-hidden="true"></i>Ver</a>
                                        </article>
                                    @empty
                                        <div class="workshop-board-empty"><i class="{{ $columna['icono'] }}" aria-hidden="true"></i><p>No hay pedidos en este estado.</p></div>
                                    @endforelse
                                </div>
                                @if($tableroPedidos[$estado]['total'] > 5)
                                    <a class="workshop-board-more" href="{{ route('admin.pedidos.index', ['estado' => $estado]) }}">Ver los {{ $tableroPedidos[$estado]['total'] }} pedidos<i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                                @endif
                            </section>
                        @endforeach
                    </div>
                    <p class="workshop-panel-note">Hasta 5 pedidos por estado. Aceptados y en fabricación: entregas más cercanas primero. Terminados y entregados: actualizaciones más recientes. Abre un pedido para continuar su seguimiento.</p>
                </section>
            @endisset
        </div>
    </div>
@stop
