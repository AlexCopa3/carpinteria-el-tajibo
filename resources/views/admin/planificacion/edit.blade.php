@extends('adminlte::page')
@section('title', 'Editar materiales del pedido')
@section('content_header')
    <x-page-heading :refined="true" title="Editar materiales del pedido" eyebrow="OPERACIONES" />
@stop
@section('content')
    <x-alerts />
    <div class="module-detail-layout planning-edit-layout">

<div class="card dashboard-card"><div class="card-body">
    <div class="order-summary-header mb-0 pb-0 border-0">
        <div class="order-summary-identity">
            <span class="order-summary-icon" aria-hidden="true"><i class="fas fa-tasks"></i></span>
            <div>
                <div class="order-summary-title">
                    <h2>Pedido {{ $pedido->id }}</h2>
                    <span class="order-summary-state {{ $pedido->estado === 'en_fabricacion' ? 'is-working' : 'is-accepted' }}">
                        <i class="{{ $pedido->estado === 'en_fabricacion' ? 'fas fa-hammer' : 'far fa-clock' }}" aria-hidden="true"></i>
                        {{ $pedido->estado_nombre }}
                    </span>
                </div>
                <p>Planificación de materiales</p>
            </div>
        </div>
    </div>
</div></div>
@php($filas=old('materiales',$pedido->detalles->flatMap->materiales->values()->toArray()))
<div class="alert alert-info">Cantidades totales para el pedido, en la unidad de cada material. Para retirar una necesidad, indica 0. No cambia el precio acordado ni las existencias.</div>
<form method="POST" action="{{ route('admin.planificacion.update',$pedido) }}" data-planificacion>@csrf @method('PUT')<input type="hidden" name="version" value="{{ old('version',$pedido->version) }}">
@foreach($pedido->detalles as $detalle)<section class="card dashboard-card form-themed form-action-update" data-plan-detalle="{{ $detalle->id }}"><div class="card-header"><h2 class="card-title"><i class="fas fa-chair mr-2" aria-hidden="true"></i>Mueble {{ $detalle->posicion }} · {{ $detalle->detalleCotizacion->tipo_nombre }}</h2></div><div class="card-body"><div data-plan-filas>
@foreach($filas as $indice=>$fila) @if((int)$fila['detalle_pedido_id']===$detalle->id) @include('admin.planificacion._fila') @endif @endforeach
</div><button type="button" class="btn btn-primary" data-plan-agregar><i class="fas fa-plus mr-2" aria-hidden="true"></i>Añadir material</button></div></section>@endforeach
<div class="card dashboard-card record-card planning-save-panel"><x-ficha-heading title="Guardar la planificación" subtitle="Explica los cambios para conservar su seguimiento." icon="fas fa-save" /><div class="card-body"><div class="form-group"><label for="motivo"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Motivo de los cambios *</label><textarea id="motivo" name="motivo" class="form-control" maxlength="2000" required>{{ old('motivo') }}</textarea></div><div class="form-actions module-actions"><a class="btn btn-cancel" href="{{ request('desde') === 'pedido' && auth()->user()->can('view', $pedido) ? route('admin.pedidos.show', $pedido) : route('admin.planificacion.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a><button class="btn btn-success"><i class="fas fa-save mr-2" aria-hidden="true"></i>Actualizar materiales</button></div></div></div>
</form>
</div>
<template id="plan-fila-nueva">@include('admin.planificacion._fila',['indice'=>'__I__','fila'=>['detalle_pedido_id'=>'__D__']])</template>

@stop
