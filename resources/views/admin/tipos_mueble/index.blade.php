@extends('adminlte::page')

@section('title', 'Tipos de muebles')
@section('content_header')
    <x-page-heading :refined="true" title="Tipos de muebles" eyebrow="CATÁLOGO" subtitle="Organiza los tipos que puedes seleccionar al cotizar.">
        @can('create', App\Models\TipoMueble::class)
            <a class="btn btn-primary" href="{{ route('admin.tipos_mueble.create') }}"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Registrar tipo de mueble</a>
        @endcan
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-auto table-list-column">
            <x-buscador placeholder="Buscar tipo de mueble por nombre" />
            <section class="card dashboard-card">
                <x-tabla-info />
                <div class="table-responsive" tabindex="0" role="region" aria-label="Tipos de muebles">
                    <table class="table table-bordered table-striped table-hover table-sm tajibo-table w-auto">
                        <thead>
                            <tr><th scope="col">Tipo de mueble</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr>
                        </thead>
                        <tbody>
                            @forelse($tipos as $tipoMueble)
                                <tr>
                                    <td><span class="categoria-nombre">{{ $tipoMueble->nombre }}</span></td>
                                    <td>
                                        <span class="record-status estado-registro {{ $tipoMueble->activo ? 'is-active' : 'is-inactive' }}">
                                            <i class="fas {{ $tipoMueble->activo ? 'fa-check-circle' : 'fa-minus-circle' }}" aria-hidden="true"></i>
                                            {{ $tipoMueble->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn btn-sm btn-info" href="{{ route('admin.tipos_mueble.show', $tipoMueble) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a>
                                            @can('update', $tipoMueble)
                                                <a class="btn btn-sm btn-success" href="{{ route('admin.tipos_mueble.edit', $tipoMueble) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><x-empty-state icon="fas fa-chair" title="No se encontraron tipos de muebles" description="Revisa la búsqueda o registra un tipo nuevo." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body pagination-wrap">{{ $tipos->links() }}</div>
            </section>
        </div>
    </div>
@stop
