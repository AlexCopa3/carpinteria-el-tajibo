@extends('adminlte::page')
@section('title', 'Pagos del pedido')
@section('content_header')
    <x-page-heading :refined="true" title="Pagos del pedido" eyebrow="ADMINISTRACIÓN / PAGOS" subtitle="Revisa el saldo y los pagos recibidos de este trabajo." />
@stop
@section('content')
    <x-alerts />
    <div class="module-detail-layout">
        <section class="card dashboard-card record-card">
            <x-ficha-heading :title="'Pedido '.$pedido->id" subtitle="Datos del cliente y estado de cobro." icon="fas fa-wallet" />
            <div class="card-body">
                <dl class="record-fields record-fields-boxed mb-3">
                    <div class="record-field"><dt><i class="fas fa-user record-value-icon" aria-hidden="true"></i>Cliente</dt><dd><strong>{{ $pedido->datos_cliente_acordados['nombre'] }}</strong></dd></div>
                    <div class="record-field"><dt><i class="fas fa-phone record-value-icon" aria-hidden="true"></i>Teléfono</dt><dd>{{ $pedido->datos_cliente_acordados['telefono'] ?? 'Sin registrar' }}</dd></div>
                </dl>
                @include('admin.pagos._resumen')
                <div class="module-actions">
                    <a class="btn btn-cancel" href="{{ request()->query('desde') === 'pedido' && auth()->user()->can('view', $pedido) ? route('admin.pedidos.show', $pedido) : route('admin.pagos.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>{{ request()->query('desde') === 'pedido' && auth()->user()->can('view', $pedido) ? 'Volver al pedido' : 'Volver al listado' }}</a>
                    @can('create', App\Models\Pago::class)
                        @if(Brick\Math\BigDecimal::of($resumen['saldo'])->isPositive())
                            <a class="btn btn-primary" href="{{ route('admin.pagos.create', ['pedido_id' => $pedido->id, 'desde' => 'pagos', 'volver_pedido' => request()->query('desde') === 'pedido' ? '1' : null]) }}"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Registrar pago</a>
                        @endif
                    @endcan
                    @if(request()->query('desde') !== 'pedido')
                        @can('view', $pedido)
                            <a class="btn btn-info" href="{{ route('admin.pedidos.show', $pedido) }}"><i class="fas fa-hammer mr-2" aria-hidden="true"></i>Ver pedido</a>
                        @endcan
                    @endif
                </div>
            </div>
        </section>

        <section class="card dashboard-card record-card">
            <x-ficha-heading title="Pagos registrados" subtitle="Historial de importes recibidos para este pedido." icon="fas fa-history" />
            <div class="card-body">
                <div class="table-responsive" tabindex="0" role="region" aria-label="Historial de pagos del pedido {{ $pedido->id }}">
                    <table class="table table-hover tajibo-table mb-0">
                        <thead><tr><th>ID</th><th>Fecha</th><th class="text-right">Importe Bs</th><th>Medio</th><th>Registrado por</th><th>Acciones</th></tr></thead>
                        <tbody>
                            @forelse($pagos as $pago)
                                <tr>
                                    <td>{{ $pago->id }}</td>
                                    <td class="text-nowrap">{{ $pago->fecha->format('d/m/Y') }}</td>
                                    <td class="text-right text-nowrap">{{ App\Support\Decimal::mostrar($pago->importe, 2) }}</td>
                                    <td>{{ $pago->medio === 'qr' ? 'QR' : 'Efectivo' }}</td>
                                    <td>{{ $pago->registradoPor?->name ?? 'Sin responsable registrado' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn btn-info" href="{{ route('admin.pagos.show', [$pago, 'desde' => 'pagos', 'volver_pedido' => request()->query('desde') === 'pedido' ? '1' : null]) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a>
                                            @can('update', $pago)
                                                <a class="btn btn-success" href="{{ route('admin.pagos.edit', [$pago, 'desde' => 'pagos', 'volver_pedido' => request()->query('desde') === 'pedido' ? '1' : null]) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty-state icon="fas fa-wallet" title="Aún no hay pagos registrados" description="Los pagos recibidos de este pedido aparecerán aquí." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $pagos->links() }}
            </div>
        </section>
    </div>
@stop
