@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" :title="$categoria->nombre" eyebrow="CATÁLOGO / CATEGORÍAS" subtitle="Detalle del registro">
        @can('update', $categoria)
            <a class="btn btn-success" href="{{ route('admin.categorias.edit', $categoria) }}">
                <i class="fas fa-edit mr-2" aria-hidden="true"></i>
                Editar
            </a>
        @endcan
    </x-page-heading>
@stop

@section('content')
    <x-alerts />
    <div class="row record-detail-cards">
        <div class="col-12 col-xl-8">
            <section class="card dashboard-card record-card">
                <x-ficha-heading title="Información de la categoría" subtitle="Clasificación y materiales asociados." icon="fas fa-tags" :activo="$categoria->activo" />
                <div class="card-body">
                    <dl class="record-fields record-fields-boxed">
                        <div class="record-field record-field-wide">
                            <dt><i class="fas fa-tag record-value-icon" aria-hidden="true"></i>Nombre</dt>
                            <dd><strong>{{ $categoria->nombre }}</strong></dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-info-circle record-value-icon" aria-hidden="true"></i>Estado</dt>
                            <dd>{{ $categoria->activo ? 'Activo' : 'Inactivo' }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-cubes record-value-icon" aria-hidden="true"></i>Materiales relacionados</dt>
                            <dd><strong>{{ $materiales->total() }}</strong></dd>
                        </div>
                    </dl>
                    <a class="btn btn-default btn-return" href="{{ route('admin.categorias.index') }}">
                        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                        Volver
                    </a>
                </div>
            </section>

            <section class="card dashboard-card record-card">
                <x-ficha-heading title="Materiales de la categoría" subtitle="Materiales registrados dentro de esta clasificación." icon="fas fa-cubes" />
                <div class="table-responsive">
                    <table class="table table-bordered table-striped tajibo-table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($materiales as $material)
                                <tr>
                                    <td><strong>{{ $material->nombre }}</strong></td>
                                    <td>{{ $material->activo ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn btn-sm btn-info" href="{{ route('admin.materiales.show', $material) }}">
                                                <i class="fas fa-eye mr-2" aria-hidden="true"></i>
                                                Ver
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">No hay materiales relacionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $materiales->links() }}</div>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <x-auditoria :registro="$categoria" />
        </div>
    </div>
@stop
