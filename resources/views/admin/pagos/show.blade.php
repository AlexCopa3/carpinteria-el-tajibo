@extends('adminlte::page')
@section('title', 'Detalle del pago')
@section('content_header')
    <x-page-heading :refined="true" title="Pago {{ $pago->id }}" eyebrow="ADMINISTRACIÓN / PAGOS" subtitle="Importe recibido, datos del registro y saldo del pedido.">
        @can('update', $pago)
            <a class="btn btn-success" href="{{ route('admin.pagos.edit', [$pago, 'desde' => in_array(request()->query('desde'), ['pedido', 'pagos'], true) ? request()->query('desde') : null, 'volver_pedido' => request()->query('volver_pedido') === '1' ? '1' : null]) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar pago</a>
        @endcan
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    <div class="module-detail-layout record-detail-cards">
        <div class="row">
            <div class="col-12 col-xl-8">
                <section class="card dashboard-card record-card">
                    <x-ficha-heading title="Información del pago" subtitle="Datos conservados al registrar el importe recibido." icon="fas fa-wallet" />
                    <div class="card-body">
                        <div class="pedido-create-total mb-3"><span>Importe registrado</span><strong>Bs {{ App\Support\Decimal::mostrar($pago->importe, 2) }}</strong></div>
                        <dl class="record-fields record-fields-boxed mb-0">
                            <div class="record-field"><dt><i class="fas fa-hammer record-value-icon" aria-hidden="true"></i>Pedido</dt><dd><strong>Pedido {{ $pago->pedido_id }}</strong></dd></div>
                            <div class="record-field"><dt><i class="fas fa-user record-value-icon" aria-hidden="true"></i>Cliente</dt><dd>{{ $pago->pedido->datos_cliente_acordados['nombre'] }}</dd></div>
                            <div class="record-field"><dt><i class="far fa-calendar-alt record-value-icon" aria-hidden="true"></i>Fecha del pago</dt><dd>{{ $pago->fecha->format('d/m/Y') }}</dd></div>
                            <div class="record-field"><dt><i class="fas fa-money-bill-wave record-value-icon" aria-hidden="true"></i>Medio</dt><dd>{{ $pago->medio === 'qr' ? 'QR' : 'Efectivo' }}</dd></div>
                            <div class="record-field"><dt><i class="fas fa-hashtag record-value-icon" aria-hidden="true"></i>Referencia</dt><dd>{{ $pago->referencia ?? '—' }}</dd></div>
                            <div class="record-field"><dt><i class="far fa-comment-alt record-value-icon" aria-hidden="true"></i>Observaciones</dt><dd>{{ $pago->observaciones ?? '—' }}</dd></div>
                        </dl>
                        <p class="small text-muted mt-3 mb-0">Registrado por {{ $pago->registradoPor->name }} · {{ $pago->created_at->format('d/m/Y H:i') }} · Versión {{ $pago->version }}</p>
                    </div>
                </section>
                <section class="card dashboard-card record-card">
                    <x-ficha-heading title="Saldo del pedido" subtitle="Resumen calculado a partir de los pagos registrados." icon="fas fa-coins" />
                    <div class="card-body">
                        @include('admin.pagos._resumen')
                        <div class="module-actions">
                            <a class="btn btn-cancel" href="{{ request()->query('desde') === 'pagos' ? route('admin.pagos.pedido', [$pago->pedido_id, 'desde' => request()->query('volver_pedido') === '1' ? 'pedido' : null]) : (request()->query('desde') === 'pedido' && auth()->user()->can('view', $pago->pedido) ? route('admin.pedidos.show', $pago->pedido) : route('admin.pagos.index')) }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>{{ request('desde') === 'pedido' && auth()->user()->can('view', $pago->pedido) ? 'Volver al pedido' : 'Volver a pagos' }}</a>
                            <a class="btn btn-info" href="{{ route('admin.pedidos.show', $pago->pedido_id) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver pedido</a>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4"><x-auditoria :registro="$pago" /></div>
        </div>
    </div>
@stop
