@extends('adminlte::page')
@section('title', 'Editar permisos del rol')
@section('content_header')
    <x-page-heading :refined="true" :title="'Permisos: '.($rol->name === 'propietario' ? 'Propietario' : 'Maestro carpintero')" eyebrow="ADMINISTRACIÓN" subtitle="Los cambios se aplican a todas las personas que tienen este rol." />
@stop
@section('content')
    <x-alerts />
    <div class="permissions-review">
        <div class="phase-notice"><i class="fas fa-info-circle" aria-hidden="true"></i>Puedes editar los permisos del rol. El grupo Usuarios está bloqueado y es exclusivo del propietario.</div>
        <form method="POST" action="{{ route('admin.permisos.update', $rol) }}">
            @csrf
            @method('PUT')
            <div class="row">
                @foreach($grupos as $modulo => $permisos)
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                        <section class="card dashboard-card form-themed form-action-update permissions-module">
                            <div class="card-header">
                                <span class="permissions-module-icon" aria-hidden="true"><i class="{{ config('gestion_permisos.iconos.'.$modulo, 'fas fa-user-shield') }}"></i></span>
                                <h2 class="card-title">{{ config('gestion_permisos.modulos.'.$modulo) }}</h2>
                            </div>
                            <div class="card-body">
                                @if($modulo === 'usuarios')
                                    <p class="small text-muted mb-2">{{ $rol->name === 'propietario' ? 'Estos permisos están siempre activos y no se pueden quitar.' : 'Estos permisos están desactivados y no se pueden asignar. Son exclusivos del propietario.' }}</p>
                                @endif
                                @foreach($permisos as $permiso)
                                    @php
                                        $bloqueado = $modulo === 'usuarios';
                                        $marcado = $bloqueado ? $rol->name === 'propietario' : in_array($permiso, (array) old('permisos', $seleccionados), true);
                                        $id = 'permiso-'.str_replace('.', '-', $permiso);
                                        $accion = explode('.', $permiso)[1];
                                    @endphp
                                    <div class="permission-option is-editable">
                                        @if($bloqueado && $marcado)
                                            <input type="hidden" name="permisos[]" value="{{ $permiso }}">
                                        @endif
                                        <div class="custom-control custom-checkbox mb-0">
                                            <input type="checkbox" class="custom-control-input" id="{{ $id }}" name="permisos[]" value="{{ $permiso }}" @checked($marcado) @disabled($bloqueado)>
                                            <label class="custom-control-label" for="{{ $id }}"><i class="fas {{ $bloqueado ? 'fa-lock' : 'fa-user-shield' }} field-label-icon" aria-hidden="true"></i>{{ config('gestion_permisos.acciones.'.$accion, ucfirst($accion)) }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                @endforeach
            </div>
            <div class="form-actions permissions-actions">
                <a class="btn btn-default btn-return" href="{{ route('admin.permisos.index') }}">
                    <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver
                </a>
                <button class="btn btn-success" type="submit">
                    <i class="fas fa-save mr-2" aria-hidden="true"></i>Guardar permisos
                </button>
            </div>
        </form>
    </div>
@stop
