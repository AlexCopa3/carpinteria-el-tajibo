@extends('adminlte::page')
@section('title', 'Detalle del pedido')
@section('content_header')
    <x-page-heading :refined="true" title="Detalle del pedido" eyebrow="OPERACIONES / PEDIDOS" subtitle="Datos acordados, materiales y seguimiento del trabajo." />
@stop
@section('content')
    <x-alerts />

<div class="row record-detail-cards"><div class="col-12 col-xl-10 pedidos-show-layout">
<div class="card dashboard-card"><div class="card-body">
    <div class="order-summary-header">
        <div class="order-summary-identity">
            <span class="order-summary-icon" aria-hidden="true"><i class="fas fa-hammer"></i></span>
            <div>
                <div class="order-summary-title">
                    <h2>Pedido {{ $pedido->id }}</h2>
                    <x-estado-pedido :pedido="$pedido" />
                </div>
                <p>Registrado el {{ $pedido->fecha_aceptacion->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="table-actions">
            @can('editarCotizacion', $pedido)
                <a class="btn btn-success" href="{{ route('admin.cotizaciones.edit', [$pedido->cotizacion_id, 'desde' => 'pedido']) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar muebles y precios</a>
            @endcan
            @can('update', $pedido)
                <a class="btn btn-success" href="{{ route('admin.pedidos.edit', [$pedido, 'desde' => 'pedido']) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar datos del pedido</a>
            @endcan
            @if((new App\Policies\PlanificacionMaterialPolicy)->view(auth()->user(), $pedido))
                <a class="btn btn-primary" href="{{ route('admin.planificacion.show', [$pedido, 'desde' => 'pedido']) }}"><i class="fas fa-tasks mr-2" aria-hidden="true"></i>{{ in_array($pedido->estado, ['terminado', 'entregado'], true) ? 'Ver planificación' : 'Planificar materiales' }}</a>
            @endif
        </div>
    </div>
    <div class="pedidos-info-grid">
        <section class="card dashboard-card record-card">
            <x-ficha-heading title="Datos del pedido" subtitle="" icon="fas fa-address-card" />
            <div class="card-body">
                <dl class="record-fields record-fields-boxed mb-0">
                    <div class="record-field"><dt><i class="fas fa-user record-value-icon" aria-hidden="true"></i>Cliente al aceptar</dt><dd><strong>{{ $pedido->datos_cliente_acordados['nombre'] }}</strong></dd></div>
                    <div class="record-field"><dt><i class="fas fa-phone record-value-icon" aria-hidden="true"></i>Teléfono</dt><dd>{{ $pedido->datos_cliente_acordados['telefono'] }}</dd></div>
                    @if(filled($pedido->datos_cliente_acordados['direccion'] ?? null))
                        <div class="record-field"><dt><i class="fas fa-map-marker-alt record-value-icon" aria-hidden="true"></i>Dirección</dt><dd>{{ $pedido->datos_cliente_acordados['direccion'] }}</dd></div>
                    @endif
                    <div class="record-field"><dt><i class="fas fa-calendar-alt record-value-icon" aria-hidden="true"></i>Entrega prevista</dt><dd>{{ $pedido->fecha_prevista_entrega?->format('d/m/Y') ?? 'Sin definir' }}</dd></div>
                    <div class="record-field"><dt><i class="fas fa-user-check record-value-icon" aria-hidden="true"></i>Responsable</dt><dd>{{ $pedido->responsable?->name ?? 'Sin asignar' }}</dd></div>
                    @if(filled($pedido->observaciones))
                        <div class="record-field record-field-wide"><dt><i class="fas fa-comment-alt record-value-icon" aria-hidden="true"></i>Observaciones</dt><dd class="pedidos-observaciones">{{ $pedido->observaciones }}</dd></div>
                    @endif
                </dl>
            </div>
        </section>
        <section class="card dashboard-card record-card">
            <x-ficha-heading title="Resumen económico" subtitle="" icon="fas fa-coins" />
            <div class="card-body">
                <div class="pedido-create-total"><span>Total acordado</span><strong data-pago-precio>Bs {{ App\Support\Decimal::mostrar($pedido->precioVigente(), 2) }}</strong></div>
                @if($resumenPagos ?? null)
                    <div class="row mt-3" aria-label="Resumen de pagos del pedido">
                        @foreach(['pagado' => 'Pagado', 'saldo' => 'Saldo pendiente'] as $campo => $titulo)
                            <div class="col-6 mb-3">
                                <div class="payment-summary-item border rounded p-3 h-100"><h3 class="h6">{{ $titulo }}</h3><p class="h4 mb-0" data-pago-{{ $campo }}>Bs {{ App\Support\Decimal::mostrar($resumenPagos[$campo], 2) }}</p></div>
                            </div>
                        @endforeach
                    </div>
                    <p class="small text-muted">{{ $resumenPagos['estado'] }}</p>
                    <div class="table-actions flex-wrap w-100">
                        <a class="btn btn-info" href="{{ route('admin.pagos.pedido', [$pedido, 'desde' => 'pedido']) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver pagos</a>
                        @can('create', App\Models\Pago::class)
                            <a class="btn btn-primary" href="{{ route('admin.pagos.create', ['pedido_id' => $pedido->id, 'desde' => 'pedido']) }}"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Registrar pago</a>
                        @endcan
                    </div>
                @endif

            </div>
        </section>
    </div>
    <section class="card dashboard-card record-card">
        <x-ficha-heading title="Muebles del pedido" subtitle="Características y precios acordados con el cliente." icon="fas fa-chair" />
        <div class="table-responsive">
            <table class="table tajibo-table mb-0">
                <thead><tr><th>Mueble</th><th>Características acordadas</th><th>Cantidad</th><th>Precio del detalle</th></tr></thead>
                <tbody>
                    @foreach($pedido->detalles as $detalle)
                        @php($origen = $detalle->detalleCotizacion)
                        <tr>
                            <td><strong class="categoria-nombre">{{ $origen->tipo_nombre }}</strong></td>
                            <td>{{ App\Support\Decimal::mostrar($origen->largo_cm) }} × {{ App\Support\Decimal::mostrar($origen->ancho_cm) }} × {{ App\Support\Decimal::mostrar($origen->alto_cm) }} cm<br>{{ $origen->material_principal_nombre }} · {{ $origen->acabado }}</td>
                            <td>{{ $detalle->cantidad_muebles }}</td>
                            <td class="text-nowrap">Bs {{ number_format($detalle->precioVigente(), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @can('update',$pedido) @if($pedido->estado==='aceptado')
        <form method="POST" action="{{ route('admin.pedidos.estado',$pedido) }}" data-iniciar-pedido>@csrf<input type="hidden" name="version" value="{{ $pedido->version }}"><input type="hidden" name="estado" value="en_fabricacion"><input type="hidden" name="clave_operacion" value="{{ Illuminate\Support\Str::uuid() }}"><button class="btn btn-primary"><i class="fas fa-hammer mr-2" aria-hidden="true"></i>Iniciar fabricación</button></form>
    @endif @endcan
    <div class="table-actions flex-wrap w-100 mt-3"><a class="btn btn-cancel" href="{{ route('admin.pedidos.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver al listado</a>@if(App\Support\Acceso::permite(auth()->user(),'cotizaciones.consultar'))<a class="btn btn-info" href="{{ route('admin.cotizaciones.show', [$cotizacionPendiente ?? $pedido->cotizacionVigente(), 'desde' => 'pedido']) }}"><i class="fas fa-file-invoice mr-2" aria-hidden="true"></i>Ver cotización</a>@endif</div>
</div></div>

@if($cotizacionPendiente ?? null)
    <section class="card dashboard-card record-card">
        <x-ficha-heading title="Cotización pendiente de confirmar" subtitle="Revisa el nuevo presupuesto antes de confirmar el cambio." icon="fas fa-file-invoice-dollar" />
        <div class="card-body">
            @if(App\Support\Acceso::permite(auth()->user(), 'costos.consultar'))
                <p>Costo actualizado: <strong>{{ $cotizacionPendiente->costo_total === null ? 'Pendiente de completar costos' : 'Bs '.App\Support\Decimal::mostrar($cotizacionPendiente->costo_total, 2) }}</strong></p>
            @endif
            @if(App\Support\Acceso::permite(auth()->user(), 'margenes.consultar'))
                <p>Precio final estimado: <strong>{{ $cotizacionPendiente->precio_sugerido === null ? 'Pendiente de definir el precio' : 'Bs '.App\Support\Decimal::mostrar($cotizacionPendiente->precio_sugerido, 2) }}</strong><small class="d-block text-muted">Pendiente de aprobación y aceptación.</small></p>
            @endif
            <div class="table-actions flex-wrap w-100" data-cotizacion-uso-acciones>
                <a class="btn btn-info" href="{{ route('admin.cotizaciones.show', [$cotizacionPendiente, 'desde' => 'pedido']) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver cotización actual</a>
                @can('update', $cotizacionPendiente)
                    <a class="btn btn-success" href="{{ route('admin.cotizaciones.edit', [$cotizacionPendiente, 'desde' => 'pedido']) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Revisar costos y precios</a>
                @endcan
            </div>
        </div>
    </section>
@endif

@include('admin.fabricacion._resumen')
@if($pedido->estado !== 'aceptado')
    @include('admin.fabricacion._cierre')
@endif
<details class="pedido-disclosure mb-3" data-pedido-seguimiento>
    <summary><i class="fas fa-history" aria-hidden="true"></i><span>Seguimiento<small>Estados y fechas del trabajo</small></span></summary>
    <div class="pedido-disclosure-body">
        <ol class="pedidos-timeline">
            @foreach($pedido->estados as $estado)
                <li><strong>{{ ['aceptado'=>'Aceptado','en_fabricacion'=>'En fabricación','terminado'=>'Terminado','entregado'=>'Entregado'][$estado->estado_nuevo] ?? $estado->estado_nuevo }}</strong><span class="d-block text-muted">{{ $estado->ocurrido_at->format('d/m/Y H:i') }} · {{ $estado->usuario->name }}</span>@if($estado->motivo)<span class="d-block">{{ $estado->motivo }}</span>@endif</li>
            @endforeach
        </ol>
    </div>
</details>

<details class="pedido-disclosure mb-3" data-pedido-registro>
    <summary><i class="fas fa-user-clock" aria-hidden="true"></i><span>Datos del registro<small>Responsables y fechas</small></span></summary>
    <div class="pedido-disclosure-body">
        <dl class="record-fields record-fields-boxed mb-0">
            <div class="record-field"><dt>Registrado por</dt><dd>{{ $pedido->registradoPor->name }}<small class="d-block text-muted">{{ $pedido->created_at->format('d/m/Y H:i') }} · Versión {{ $pedido->version }}</small></dd></div>
            @can('verUsoMateriales', $pedido)
                @if($pedido->materiales_validados_at)
                    <div class="record-field"><dt>Materiales validados por</dt><dd>{{ $pedido->materialesValidadosPor?->name }}<small class="d-block text-muted">{{ $pedido->materiales_validados_at->format('d/m/Y H:i') }}</small></dd></div>
                @endif
                @foreach($pedido->detalles as $detalle)
                    @foreach($detalle->materiales as $fila)
                        @if($fila->uso_registrado_at)
                            <div class="record-field"><dt>{{ $fila->material_nombre }} · Mueble {{ $detalle->posicion }}</dt><dd>{{ $fila->usoRegistradoPor?->name ?? 'Sin responsable registrado' }}<small class="d-block text-muted">{{ $fila->uso_registrado_at->format('d/m/Y H:i') }}</small></dd></div>
                        @endif
                    @endforeach
                @endforeach
            @endcan
            @if($pedido->precio_confirmado_at)
                <div class="record-field"><dt>Precio confirmado por</dt><dd>{{ $pedido->precioConfirmadoPor?->name }}<small class="d-block text-muted">{{ $pedido->precio_confirmado_at->format('d/m/Y H:i') }}</small></dd></div>
            @endif
        </dl>
    </div>
</details>
@if(App\Support\Acceso::permite(auth()->user(),'casos.consultar') && in_array($pedido->estado,['terminado','entregado'],true) && $pedido->detalles->contains(fn($detalle) => $detalle->casoHistorico !== null))
    <details class="pedido-disclosure mb-3">
        <summary><i class="fas fa-layer-group" aria-hidden="true"></i><span>Casos de este pedido<small>Antecedentes generados del trabajo</small></span></summary>
        <div class="pedido-disclosure-body table-actions flex-wrap w-100">
            @foreach($pedido->detalles as $detalle)
                @if($detalle->casoHistorico)
                    <a class="btn btn-info" href="{{ route('admin.casos.show', [$detalle->casoHistorico, 'desde' => 'pedido']) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver caso {{ $detalle->casoHistorico->id }}</a>
                @endif
            @endforeach
        </div>
    </details>
@endif
</div></div>
@stop
