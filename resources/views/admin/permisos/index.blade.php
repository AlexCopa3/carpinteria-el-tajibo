@extends('adminlte::page')
@section('title', 'Permisos por rol')
@section('content_header')
    <x-page-heading :refined="true" title="Permisos por rol" eyebrow="ADMINISTRACIÓN" subtitle="Revisa las funciones disponibles para el propietario y los maestros.">
        <a class="btn btn-default btn-return" href="{{ route('admin.usuarios.index') }}">
            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
            Volver a usuarios
        </a>
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    <div class="row permissions-review">
        <div class="col-12 col-lg-10 col-xl-8 permissions-index-column">
            <div class="card dashboard-card record-card">
                <x-ficha-heading title="Roles del sistema" subtitle="Consulta y revisa los permisos de cada rol." icon="fas fa-user-shield" />
                <div class="table-responsive">
                    <table class="table tajibo-table">
                        <thead>
                            <tr>
                                <th scope="col">Rol</th>
                                <th scope="col">Permisos asignados</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $rol)
                                <tr>
                                    <td>
                                        <span class="role-pill {{ $rol->name === 'propietario' ? 'role-owner' : '' }}">
                                            {{ $rol->name === 'propietario' ? 'Propietario' : 'Maestro carpintero' }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $rol->permissions_count }}</strong></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn btn-success btn-sm" href="{{ route('admin.permisos.edit', $rol) }}">
                                                <i class="fas fa-check-square mr-2" aria-hidden="true"></i>Revisar permisos
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
