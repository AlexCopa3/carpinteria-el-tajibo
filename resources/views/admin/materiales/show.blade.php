@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" :title="$material->nombre" eyebrow="CATÁLOGO / MATERIALES" :subtitle="$material->activo?'Material activo':'Material inactivo'">
        @can('update',$material)
            <a class="btn btn-success" href="{{ route('admin.materiales.edit',$material) }}">
                <i class="fas fa-edit mr-2" aria-hidden="true"></i>
                Editar material
            </a>
        @endcan
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    <div class="row record-detail-cards">
        <div class="col-12 col-xl-8">
            <section class="card dashboard-card record-card">
                <x-ficha-heading title="Detalle del material" subtitle="Características y costo de referencia para cotizar." icon="fas fa-cubes" :activo="$material->activo" />
                <div class="card-body">
                    <dl class="record-fields record-fields-boxed">
                        <div class="record-field record-field-wide">
                            <dt><i class="fas fa-align-left record-value-icon" aria-hidden="true"></i>Descripción</dt>
                            <dd>{{ $material->descripcion ?? '—' }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-tags record-value-icon" aria-hidden="true"></i>Categoría</dt>
                            <dd>{{ $material->categoriaMaterial?->nombre }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-ruler-combined record-value-icon" aria-hidden="true"></i>Unidad de medida</dt>
                            <dd>{{ $material->unidad->nombre }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-list-alt record-value-icon" aria-hidden="true"></i>Especificaciones</dt>
                            <dd>{{ $material->especificaciones ?? '—' }}</dd>
                        </div>
                        @can('costos.consultar')
                            <div class="record-field">
                                <dt><i class="fas fa-coins record-value-icon" aria-hidden="true"></i>Referencia por unidad</dt>
                                <dd><strong>Bs {{ App\Support\Decimal::mostrar($material->precio_referencia,6) }}</strong></dd>
                            </div>
                        @endcan
                    </dl>
                    <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                        <a class="btn btn-default btn-return" href="{{ route('admin.materiales.index') }}">
                            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver a materiales
                        </a>
                        <a class="btn btn-info" href="{{ route('admin.existencias.index', ['material_id' => $material->id]) }}">
                            <i class="fas fa-cubes mr-2" aria-hidden="true"></i>Ver existencias
                        </a>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <x-auditoria :registro="$material" />
        </div>
    </div>
@stop
