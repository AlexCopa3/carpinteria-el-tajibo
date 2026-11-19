@extends('adminlte::page')
@section('title', 'Pagos')
@section('content_header')
    <x-page-heading :refined="true" title="Pagos" eyebrow="ADMINISTRACIÓN" subtitle="Consulta lo pagado y lo pendiente de cada pedido." />
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-auto mw-100 module-list-content">
            <div class="card dashboard-card">
                <div class="card-body">
                    <x-buscador placeholder="Buscar por ID de pedido o nombre del cliente" />
                    <div class="table-responsive" tabindex="0" role="region" aria-label="Estado de pagos por pedido">
                        <table class="table table-hover tajibo-table w-auto mb-0">
                            <thead>
                                <tr><th>Pedido</th><th>Cliente</th><th class="text-right">Total acordado Bs</th><th class="text-right">Pagado Bs</th><th class="text-right">Saldo pendiente Bs</th><th>Estado de cobro</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                @forelse($pedidos as $pedido)
                                    @php($resumen = $resumenes[$pedido->id])
                                    <tr>
                                        <td class="text-nowrap"><strong class="categoria-nombre">Pedido {{ $pedido->id }}</strong></td>
                                        <td><strong class="categoria-nombre">{{ $pedido->datos_cliente_acordados['nombre'] }}</strong>@if(!empty($pedido->datos_cliente_acordados['telefono']))<small class="d-block text-muted">{{ $pedido->datos_cliente_acordados['telefono'] }}</small>@endif</td>
                                        <td class="text-right text-nowrap">{{ App\Support\Decimal::mostrar($resumen['precio'], 2) }}</td>
                                        <td class="text-right text-nowrap">{{ App\Support\Decimal::mostrar($resumen['pagado'], 2) }}</td>
                                        <td class="text-right text-nowrap"><strong>{{ App\Support\Decimal::mostrar($resumen['saldo'], 2) }}</strong></td>
                                        <td><span class="record-status {{ $resumen['estado'] === 'Pagado' ? 'is-active' : 'is-inactive' }}">{{ $resumen['estado'] }}</span></td>
                                        <td><div class="table-actions"><a class="btn btn-info" href="{{ route('admin.pagos.pedido', $pedido) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a></div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted">No hay pedidos para estos filtros.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $pedidos->links() }}
                </div>
            </div>
        </div>
    </div>
@stop
