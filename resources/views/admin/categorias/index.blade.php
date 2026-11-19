@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <div class="row">
        <div class="col-12">
            <x-page-heading :refined="true" title="Categorías" eyebrow="CATÁLOGO" subtitle="Organiza los materiales para encontrarlos con facilidad.">
                @can('create', App\Models\Categoria::class)
                    <a class="btn btn-primary" href="{{ route('admin.categorias.create') }}">
                        <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                        Registrar categoría
                    </a>
                @endcan
            </x-page-heading>
        </div>
    </div>
@stop

@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-12 col-xl-10 table-list-column">
    <x-buscador placeholder="Buscar categoría por nombre" />
    <section class="card dashboard-card">
        <x-tabla-info />
        <div class="table-responsive" tabindex="0" role="region" aria-label="Tabla de categorías desplazable horizontalmente">
            <table class="table table-bordered table-striped table-hover table-sm tajibo-table">
                <caption class="sr-only">Categorías de materiales</caption>
                <thead>
                    <tr>
                        <th scope="col">Categoría</th>
                        <th scope="col">Materiales</th>
                        <th scope="col">Estado</th><th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categorias as $categoria)
                        <tr>
                            <td><span class="categoria-nombre">{{ $categoria->nombre }}</span></td>
                            <td>{{ $categoria->materiales_count }}</td>
                            <td>
                                <span class="record-status estado-registro {{ $categoria->activo ? 'is-active' : 'is-inactive' }}">
                                    <i class="fas {{ $categoria->activo ? 'fa-check-circle' : 'fa-minus-circle' }}" aria-hidden="true"></i>
                                    {{ $categoria->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-info" href="{{ route('admin.categorias.show', $categoria) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a>
                                    @can('update', $categoria)
                                        <a class="btn btn-sm btn-success" href="{{ route('admin.categorias.edit', $categoria) }}">
                                            <i class="fas fa-edit mr-2" aria-hidden="true"></i>
                                            Editar
                                        </a>
                                    @endcan
                                    <x-eliminar :registro="$categoria" :ruta="route('admin.categorias.destroy', $categoria)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                @if(request()->filled('q'))
                                    <x-empty-state icon="fas fa-search" title="No se encontraron categorías" description="Prueba con otro nombre o limpia la búsqueda para ver todas las categorías." />
                                @else
                                    <x-empty-state icon="fas fa-tags" title="Aún no hay categorías" description="Las categorías registradas aparecerán aquí para organizar tus materiales." />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $categorias->links() }}
        </div>
    </section>
        </div>
    </div>
@stop
