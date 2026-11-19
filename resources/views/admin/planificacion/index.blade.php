@extends('adminlte::page')
@section('title', 'Planificación de materiales')
@section('content_header')
    <x-page-heading :refined="true" title="Planificación de materiales" eyebrow="OPERACIONES" subtitle="Revisa los materiales de cada pedido; las cantidades ya vienen de la cotización." />
@stop
@section('content')
    <x-alerts />
    <div class="row"><div class="col-auto mw-100 module-list-content">
        <div class="card dashboard-card"><div class="card-body">
            <x-buscador placeholder="Buscar por nombre del cliente" />
            <div class="table-responsive" tabindex="0" role="region" aria-label="Pedidos para planificar materiales">
                <table class="table table-hover tajibo-table w-auto mb-0">
                    <thead><tr><th>Pedido</th><th>Cliente</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @forelse($pedidos as $pedido)
                            <tr>
                                <td class="text-nowrap">
                                    @can('view', $pedido)
                                        <a class="categoria-nombre" href="{{ route('admin.pedidos.show', $pedido) }}">Pedido {{ $pedido->id }}</a>
                                    @else
                                        <span class="categoria-nombre">Pedido {{ $pedido->id }}</span>
                                    @endcan
                                </td>
                                <td><strong class="categoria-nombre">{{ $pedido->datos_cliente_acordados['nombre'] }}</strong></td><td><x-estado-pedido :pedido="$pedido" /></td><td><div class="table-actions"><a class="btn btn-info" href="{{ route('admin.planificacion.show', $pedido) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver materiales</a></div></td></tr>
                        @empty
                            <tr><td colspan="4">No hay pedidos para planificar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $pedidos->links() }}
        </div></div>
    </div></div>
@stop
