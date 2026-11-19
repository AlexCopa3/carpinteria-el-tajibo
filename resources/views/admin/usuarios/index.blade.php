@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <div class="row">
        <div class="col-12">
            <x-page-heading :refined="true" title="Usuarios" eyebrow="ADMINISTRACIÓN" subtitle="Las personas que dan vida a tu taller.">
                <a class="btn btn-primary" href="{{ route('admin.usuarios.create') }}">
                    <i class="fas fa-plus mr-2" aria-hidden="true"></i>
                    Crear cuenta
                </a>
                @can('viewAny', Spatie\Permission\Models\Role::class)
                    <a class="btn btn-info" href="{{ route('admin.permisos.index') }}">
                        <i class="fas fa-user-shield mr-2" aria-hidden="true"></i>
                        Ver permisos
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
    <section class="card dashboard-card">
        <div class="card-header">
            <h2>Equipo de El Tajibo</h2>
            <span class="section-tag">
                {{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'cuenta' : 'cuentas' }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm tajibo-table mb-0">
                <caption class="sr-only">Cuentas de usuarios y roles asignados</caption>
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Correo electrónico</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        <tr>
                            <td>
                                <strong class="usuario-nombre">
                                    {{ $usuario->name }}
                                </strong>
                                @if($usuario->is(auth()->user()))
                                    <span class="text-muted small ml-2">
                                        Tú
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{ $usuario->email }}
                            </td>
                            <td>
                                <span class="role-pill {{ $usuario->hasRole('propietario') ? 'role-owner' : '' }}">
                                    {{ $usuario->hasRole('propietario') ? 'Propietario' : ($usuario->hasRole('maestro') ? 'Maestro carpintero' : 'Sin rol') }}
                                </span>
                            </td>
                            <td>
                                <span class="record-status estado-registro {{ $usuario->activo ? 'is-active' : 'is-inactive' }}">
                                    <i class="fas {{ $usuario->activo ? 'fa-check-circle' : 'fa-minus-circle' }}" aria-hidden="true"></i>
                                    {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-info" href="{{ route('admin.usuarios.show', $usuario) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a>
                                    <a class="btn btn-sm btn-success" href="{{ route('admin.usuarios.edit', $usuario) }}">
                                        <i class="fas fa-edit mr-2" aria-hidden="true"></i>
                                        Editar
                                        <span class="sr-only">
                                            cuenta de {{ $usuario->name }}
                                        </span>
                                    </a>
                                    @if(!$usuario->hasRole('propietario'))
                                        @can('asignarPermisos', $usuario)
                                            <a class="btn btn-sm btn-history" href="{{ route('admin.usuarios.permisos.edit', $usuario) }}">
                                                <i class="fas fa-user-shield mr-2" aria-hidden="true"></i>Permisos
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state title="Aún no hay cuentas" description="Crea una cuenta para incorporar a tu equipo." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination-wrap">
            {{ $usuarios->links() }}
        </div>
    </section>
        </div>
    </div>
@stop
