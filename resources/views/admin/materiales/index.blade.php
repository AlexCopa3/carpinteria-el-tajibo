@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <div class="row">
        <div class="col-12">
            <x-page-heading :refined="true" title="Materiales" eyebrow="CATÁLOGO" subtitle="Especificaciones y costos de referencia en bolivianos.">
                @can('create',App\Models\Material::class)
                    <a class="btn btn-primary" href="{{ route('admin.materiales.create') }}">
                        <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                        Nuevo material
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
    <x-buscador />
    <div class="card dashboard-card">
        <x-tabla-info />
        <div class="table-responsive" tabindex="0" role="region" aria-label="Tabla desplazable horizontalmente">
            <table class="table table-bordered table-striped table-hover table-sm tajibo-table">
                <caption class="sr-only">Catálogo de materiales</caption>
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Categoría / especificaciones</th>
                        <th>Unidad</th>
                        @can('costos.consultar')
                            <th>Costo Bs / unidad</th>
                        @endcan
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materiales as $material)
                        <tr>
                            <td>
                                <a href="{{ route('admin.materiales.show',$material) }}">
                                    <strong>
                                        {{ $material->nombre }}
                                    </strong>
                                </a>
                            </td>
                            <td>
                                {{ $material->categoriaMaterial?->nombre }}
                                <br>
                                <small class="text-muted">
                                    {{ $material->especificaciones }}
                                </small>
                            </td>
                            <td>
                                {{ $material->unidad->nombre }}
                            </td>
                            @can('costos.consultar')
                                <td>
                                    Bs {{ App\Support\Decimal::mostrar($material->precio_referencia,6) }}
                                </td>
                            @endcan
                            <td>
                                <span class="record-status estado-registro {{ $material->activo ? 'is-active' : 'is-inactive' }}">
                                    <i class="fas {{ $material->activo ? 'fa-check-circle' : 'fa-minus-circle' }}" aria-hidden="true"></i>
                                    {{ $material->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-info" href="{{ route('admin.materiales.show', $material) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a>
                                    @can('update',$material)
                                        <a class="btn btn-sm btn-success" href="{{ route('admin.materiales.edit',$material) }}">
                                            <i class="fas fa-edit mr-2" aria-hidden="true"></i>
                                            Editar
                                        </a>
                                    @endcan
                                    <x-eliminar :registro="$material" :ruta="route('admin.materiales.destroy', $material)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state title="Tu catálogo está listo para empezar" description="Registra materiales, su categoría, unidad y costo de referencia." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $materiales->links() }}
        </div>
    </div>
        </div>
    </div>
@stop
