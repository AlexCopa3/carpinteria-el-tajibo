@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <div class="row">
        <div class="col-12">
            <x-page-heading :refined="true" title="Existencias de materiales" eyebrow="TALLER" subtitle="Consulta cuánto material tienes y actualiza las cantidades.">
                @if(request()->filled('material_id'))
                    <a class="btn btn-default btn-return" href="{{ route('admin.existencias.index') }}"><i class="fas fa-list mr-2" aria-hidden="true"></i>Listado general</a>
                @endif
                <a class="btn btn-info" href="{{ route('admin.materiales.index') }}"><i class="fas fa-cubes mr-2" aria-hidden="true"></i>Ver materiales</a>
            </x-page-heading>
        </div>
    </div>
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-auto table-list-column">
    <form method="GET" class="search-toolbar mb-3">
        <label for="q" class="sr-only">Buscar material o categoría</label>
        <input class="form-control" id="q" name="q" value="{{ request('q') }}" maxlength="200" placeholder="Buscar material o categoría">
        <button class="btn btn-primary btn-search" type="submit"><i class="fas fa-search mr-2" aria-hidden="true"></i>Buscar</button>
        <a class="btn btn-light btn-clear" href="{{ route('admin.existencias.index') }}"><i class="fas fa-eraser mr-2" aria-hidden="true"></i>Limpiar</a>
    </form>
    <section class="card dashboard-card">
        <x-tabla-info />
        <div class="table-responsive" tabindex="0" role="region" aria-label="Existencias de materiales">
            <table class="table table-bordered table-striped table-hover table-sm tajibo-table w-auto">
                <thead>
                    <tr>
                        <th scope="col">Material</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Existencia</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materiales as $material)
                        <tr>
                            <td><a href="{{ route('admin.materiales.show', $material) }}"><strong>{{ $material->nombre }}</strong></a></td>
                            <td>{{ $material->categoriaMaterial?->nombre ?? '—' }}</td>
                            <td>{{ App\Support\Decimal::mostrar($material->existencia?->cantidad ?? '0') }} {{ $material->unidad->nombre }}</td>
                            <td><span class="record-status estado-registro {{ $material->activo ? 'is-active' : 'is-inactive' }}"><i class="fas {{ $material->activo ? 'fa-check-circle' : 'fa-minus-circle' }}" aria-hidden="true"></i>{{ $material->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm px-2 btn-default btn-history" href="{{ route('admin.existencias.show', $material) }}"><i class="fas fa-history mr-1" aria-hidden="true"></i>Ver movimientos</a>
                                    @can('cantidades', $material)
                                        <a class="btn btn-sm px-2 btn-primary" href="{{ route('admin.existencias.create', $material) }}"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Agregar cantidad</a>
                                        <a class="btn btn-sm px-2 btn-success" href="{{ route('admin.existencias.edit', $material) }}"><i class="fas fa-edit mr-1" aria-hidden="true"></i>Editar cantidad</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No hay materiales para mostrar" description="Registra un material en el catálogo o cambia la búsqueda." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $materiales->links() }}</div>
    </section>
        </div>
    </div>
@stop
