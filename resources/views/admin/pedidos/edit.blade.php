@extends('adminlte::page')
@section('title', 'Editar pedido')
@section('content_header')
    <x-page-heading :refined="true" title="Editar pedido" eyebrow="OPERACIONES / PEDIDOS" subtitle="Actualiza la entrega, el responsable y las observaciones." />
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-12 col-lg-10 col-xl-8 pedidos-edit-layout">
            @can('editarCotizacion', $pedido)
                <section class="card dashboard-card record-card">
                    <x-ficha-heading title="Muebles, cantidades y precios" subtitle="Edita directamente la cotización y confirma el precio cuando el cliente lo acepte." icon="fas fa-chair" />
                    <div class="card-body"><a class="btn btn-success" href="{{ route('admin.cotizaciones.edit', [$pedido->cotizacion_id, 'desde' => 'pedido']) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar muebles y precios</a></div>
                </section>
            @endcan
            <section class="card dashboard-card form-panel form-themed form-action-update">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Pedido {{ $pedido->id }} · {{ $pedido->estado_nombre }}</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">El cliente, los muebles y el precio acordado se conservan. Versión {{ $pedido->version }}.</p>
                    <form method="POST" action="{{ route('admin.pedidos.update', $pedido) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="version" value="{{ old('version', $pedido->version) }}">
                        @include('admin.pedidos._datos')
                        <div class="form-actions pedidos-edit-actions">
                            <a class="btn btn-cancel" href="{{ request('desde') === 'pedido' && auth()->user()->can('view', $pedido) ? route('admin.pedidos.show', $pedido) : route('admin.pedidos.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                            <button class="btn btn-success" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>Actualizar pedido</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@stop
