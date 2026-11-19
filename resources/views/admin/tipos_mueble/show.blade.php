@extends('adminlte::page')

@section('title', 'Tipo de mueble')
@section('content_header')
    <x-page-heading :refined="true" :title="$tipoMueble->nombre" eyebrow="CATÁLOGO / TIPOS DE MUEBLES" subtitle="Detalle del registro">
        @can('update', $tipoMueble)
            <a class="btn btn-success" href="{{ route('admin.tipos_mueble.edit', $tipoMueble) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar</a>
        @endcan
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    <div class="row record-detail-cards">
        <div class="col-12 col-xl-8">
            <section class="card dashboard-card record-card">
                <x-ficha-heading title="Información del tipo de mueble" subtitle="Nombre y estado para las nuevas cotizaciones." icon="fas fa-chair" :activo="$tipoMueble->activo" />
                <div class="card-body">
                    <dl class="record-fields record-fields-boxed">
                        <div class="record-field">
                            <dt><i class="fas fa-chair record-value-icon" aria-hidden="true"></i>Nombre</dt>
                            <dd><strong>{{ $tipoMueble->nombre }}</strong></dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-info-circle record-value-icon" aria-hidden="true"></i>Estado</dt>
                            <dd>{{ $tipoMueble->activo ? 'Activo' : 'Inactivo' }}</dd>
                        </div>
                    </dl>
                    <a class="btn btn-return" href="{{ route('admin.tipos_mueble.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver</a>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-4"><x-auditoria :registro="$tipoMueble" /></div>
    </div>
@stop
