@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" :title="$cliente->nombre" eyebrow="CLIENTES" :subtitle="$cliente->activo ? 'Cliente activo' : 'Cliente inactivo'">
        @can('update', $cliente)
            <a class="btn btn-success" href="{{ route('admin.clientes.edit', $cliente) }}">
                <i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar cliente
            </a>
        @endcan
    </x-page-heading>
@stop

@section('content')
    <x-alerts />
    <div class="row record-detail-cards">
        <div class="col-12 col-xl-8">
            <section class="card dashboard-card record-card">
                <x-ficha-heading title="Información del cliente" subtitle="Datos de contacto y observaciones." icon="fas fa-user" :activo="$cliente->activo" />
                <div class="card-body">
                    <dl class="record-fields record-fields-boxed">
                        <div class="record-field record-field-wide">
                            <dt><i class="fas fa-user record-value-icon" aria-hidden="true"></i>Nombre</dt>
                            <dd><strong>{{ $cliente->nombre }}</strong></dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-address-card record-value-icon" aria-hidden="true"></i>Tipo</dt>
                            <dd>{{ ucfirst($cliente->tipo) }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-phone record-value-icon" aria-hidden="true"></i>Teléfono</dt>
                            <dd>{{ $cliente->telefono }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-envelope record-value-icon" aria-hidden="true"></i>Correo</dt>
                            <dd>{{ $cliente->email ?? '—' }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-map-marker-alt record-value-icon" aria-hidden="true"></i>Dirección</dt>
                            <dd>{{ $cliente->direccion ?? '—' }}</dd>
                        </div>
                        <div class="record-field record-field-wide">
                            <dt><i class="fas fa-comment-alt record-value-icon" aria-hidden="true"></i>Observaciones</dt>
                            <dd>{{ $cliente->observaciones ?? '—' }}</dd>
                        </div>
                    </dl>
                    <a class="btn btn-default btn-return" href="{{ route('admin.clientes.index') }}">
                        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver a clientes
                    </a>
                </div>
            </section>
            @if($pedidosCliente !== null)
                <section id="pedidos-cliente" class="card dashboard-card record-card">
                    <x-ficha-heading title="Pedidos del cliente" subtitle="Pedidos asociados a este cliente, del más reciente al más antiguo." icon="fas fa-hammer" />
                    <div class="card-body">
                        <div class="table-responsive" tabindex="0" role="region" aria-label="Pedidos del cliente">
                            <table class="table table-hover tajibo-table w-auto mb-0">
                                <thead><tr><th>ID</th><th>Fecha</th><th>Entrega prevista</th><th>Estado</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    @forelse($pedidosCliente as $pedido)
                                        <tr>
                                            <td><strong class="categoria-nombre">{{ $pedido->id }}</strong></td>
                                            <td>{{ $pedido->fecha_aceptacion->format('d/m/Y') }}</td>
                                            <td>{{ $pedido->fecha_prevista_entrega?->format('d/m/Y') ?? 'Sin definir' }}</td>
                                            <td><x-estado-pedido :pedido="$pedido" /></td>
                                            <td><div class="table-actions"><a class="btn btn-info" href="{{ route('admin.pedidos.show', $pedido) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a></div></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5">Este cliente todavía no tiene pedidos registrados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $pedidosCliente->links() }}
                    </div>
                </section>
            @endif
        </div>
        <div class="col-12 col-xl-4">
            <x-auditoria :registro="$cliente" />
        </div>
    </div>
@stop
