@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Clientes" eyebrow="OPERACIONES" subtitle="Información de contacto, siempre a mano.">
        @can('create',App\Models\Cliente::class)
            <a href="{{ route('admin.clientes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                Nuevo cliente
            </a>
        @endcan
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-auto table-list-column">
    <x-buscador />
    <section class="card dashboard-card">
        <x-tabla-info />
        <div class="table-responsive" tabindex="0" role="region" aria-label="Tabla desplazable horizontalmente">
            <table class="table table-bordered table-striped table-hover table-sm tajibo-table w-auto">
                <caption class="sr-only">Listado de clientes</caption>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                        <tr>
                            <td>
                                <a href="{{ route('admin.clientes.show',$cliente) }}">
                                    <strong>{{ $cliente->nombre }}</strong>
                                </a>
                            </td>
                            <td>
                                {{ ucfirst($cliente->tipo) }}
                            </td>
                            <td class="text-nowrap">
                                {{ $cliente->telefono }}
                            </td>
                            <td class="text-break">
                                {{ $cliente->email ?? '—' }}
                            </td>
                            <td>
                                <span class="record-status estado-registro {{ $cliente->activo ? 'is-active' : 'is-inactive' }}">
                                    <i class="fas {{ $cliente->activo ? 'fa-check-circle' : 'fa-minus-circle' }}" aria-hidden="true"></i>
                                    {{ $cliente->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-info" href="{{ route('admin.clientes.show', $cliente) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a>
                                    @can('update',$cliente)
                                        <a href="{{ route('admin.clientes.edit',$cliente) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-edit mr-2" aria-hidden="true"></i>
                                            Editar
                                        </a>
                                    @endcan
                                    <x-eliminar :registro="$cliente" :ruta="route('admin.clientes.destroy', $cliente)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state title="Sin clientes para mostrar" description="Los clientes registrados aparecerán aquí." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $clientes->links() }}
        </div>
    </section>
        </div>
    </div>
@stop
