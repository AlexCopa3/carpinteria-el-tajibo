@extends('adminlte::page')
@section('title', 'Pedidos')
@section('content_header')
    <div class="row">
        <div class="col-12 col-xl-10">
            <x-page-heading :refined="true" title="Pedidos" eyebrow="OPERACIONES" subtitle="Consulta los pedidos aceptados y su avance de fabricación." />
        </div>
    </div>
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-auto table-list-column pedidos-list-layout">

<div class="card dashboard-card">
<div class="card-body">
    <p class="text-muted">Los pedidos se crean desde una cotización aprobada cuando el cliente acepta.</p>
    <form method="GET" class="row mb-3 list-filters">
        <div class="col-12 col-md-5 form-group">
<label for="q">
<i class="fas fa-search field-label-icon" aria-hidden="true">
</i>Buscar por cliente o ID</label>
<input id="q" name="q" value="{{ request('q') }}" class="form-control">
</div>
        <div class="col-12 col-md-3 form-group">
<label for="estado">
<i class="fas fa-toggle-on field-label-icon" aria-hidden="true">
</i>Estado</label>
<select id="estado" name="estado" class="form-control">
<option value="">Todos</option>@foreach(['aceptado'=>'Aceptado','en_fabricacion'=>'En fabricación','terminado'=>'Terminado','entregado'=>'Entregado'] as $valor=>$nombre)<option value="{{ $valor }}" @selected(request('estado')===$valor)>{{ $nombre }}</option>@endforeach</select>
</div>
        <div class="col-12 col-md-4 form-group align-self-end list-filter-actions">
<button class="btn btn-primary">
<i class="fas fa-search mr-2" aria-hidden="true">
</i>Buscar</button>
<a class="btn btn-clear" href="{{ route('admin.pedidos.index') }}">
<i class="fas fa-eraser mr-2" aria-hidden="true">
</i>Limpiar</a>
</div>
    </form>
    <div class="table-responsive">
<table class="table table-hover tajibo-table">
<thead>
<tr>
<th>ID</th>
<th>Cliente</th>
<th>Fecha</th>
<th>Entrega prevista</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
        
@forelse($pedidos as $pedido)<tr>
<td>{{ $pedido->id }}</td>
<td>
<strong class="categoria-nombre">{{ $pedido->datos_cliente_acordados['nombre'] }}</strong>
</td>
<td>{{ $pedido->fecha_aceptacion->format('d/m/Y') }}</td>
<td>{{ $pedido->fecha_prevista_entrega?->format('d/m/Y') ?? 'Sin definir' }}</td>
<td>
<x-estado-pedido :pedido="$pedido" />
</td>
<td>
<div class="table-actions">
<a class="btn btn-sm btn-info" href="{{ route('admin.pedidos.show',$pedido) }}">
<i class="fas fa-eye mr-2" aria-hidden="true">
</i>Ver</a>@can('update',$pedido)<a class="btn btn-sm btn-success" href="{{ route('admin.pedidos.edit',$pedido) }}">
<i class="fas fa-edit mr-2" aria-hidden="true">
</i>Editar</a>@endcan</div>
</td>
</tr>
@empty<tr>
<td colspan="6">No hay pedidos para mostrar.</td>
</tr>
@endforelse
    </tbody>
</table>
</div>{{ $pedidos->links() }}
</div>
</div>

        </div>
    </div>
@stop
