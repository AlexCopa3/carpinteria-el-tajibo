@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" :title="$usuario->name" eyebrow="ADMINISTRACIÓN / USUARIOS" subtitle="Detalle del registro">
        @can('update', $usuario)
            <a class="btn btn-success" href="{{ route('admin.usuarios.edit', $usuario) }}">
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
                <x-ficha-heading title="Información de la cuenta" subtitle="Datos del usuario y rol asignado." icon="fas fa-user-shield" :activo="$usuario->activo" />
                <div class="card-body">
                    <dl class="record-fields record-fields-boxed">
                        <div class="record-field record-field-wide">
                            <dt><i class="fas fa-user record-value-icon" aria-hidden="true"></i>Nombre</dt>
                            <dd><strong>{{ $usuario->name }}</strong></dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-envelope record-value-icon" aria-hidden="true"></i>Correo electrónico</dt>
                            <dd>{{ $usuario->email }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-id-card record-value-icon" aria-hidden="true"></i>Identificación</dt>
                            <dd>{{ $usuario->personal?->numero_identificacion ? strtoupper($usuario->personal?->tipo_identificacion).' · '.$usuario->personal?->numero_identificacion : 'Sin completar' }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-phone record-value-icon" aria-hidden="true"></i>Teléfono</dt>
                            <dd>{{ $usuario->personal?->telefono ?: 'Sin registrar' }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-map-marker-alt record-value-icon" aria-hidden="true"></i>Dirección</dt><dd>{{ $usuario->personal?->direccion ?: 'Sin registrar' }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-user-shield record-value-icon" aria-hidden="true"></i>Rol</dt>
                            <dd>{{ $usuario->roles->pluck('name')->map(fn ($rol) => $rol === 'maestro' ? 'Maestro carpintero' : ucfirst($rol))->join(', ') ?: 'Sin rol' }}</dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-toggle-on record-value-icon" aria-hidden="true"></i>Estado</dt>
                            <dd>{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</dd>
                        </div>
                    </dl>
                    <a class="btn btn-default btn-return" href="{{ route('admin.usuarios.index') }}">
                        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                        Volver
                    </a>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <x-auditoria :registro="$usuario" />
        </div>
    </div>
@stop
