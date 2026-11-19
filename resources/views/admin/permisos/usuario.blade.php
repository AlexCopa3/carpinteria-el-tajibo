@extends('adminlte::page')

@section('title', 'Permisos individuales')
@section('content_header')
    <x-page-heading :refined="true" :title="'Permisos de '.$usuario->name" eyebrow="ADMINISTRACIÓN / USUARIOS" subtitle="Selecciona las funciones que podrá utilizar esta persona." />
@stop
@section('content')
    <x-alerts />
    <div class="permissions-review">
        <div class="phase-notice">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>Marca para permitir y desmarca para quitar. Los cambios afectan solo a esta persona. Usuarios está reservado al propietario.</span>
        </div>
        <form id="permisos-individuales" method="POST" action="{{ route('admin.usuarios.permisos.update', $usuario) }}">
            @csrf
            @method('PUT')
            <div class="row">
                @foreach($grupos as $modulo => $permisos)
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                        <section class="card dashboard-card form-themed form-action-update permissions-module">
                            <div class="card-header">
                                <span class="permissions-module-icon" aria-hidden="true"><i class="{{ config('gestion_permisos.iconos.'.$modulo, 'fas fa-user-shield') }}"></i></span>
                                <h2 class="card-title">{{ config('gestion_permisos.modulos.'.$modulo, ucfirst($modulo)) }}</h2>
                            </div>
                            <div class="card-body">
                                @if($modulo === 'usuarios')
                                    <p class="small text-muted mb-2">{{ $usuario->hasRole('propietario') ? 'Estos permisos están siempre activos y no se pueden quitar.' : 'Estos permisos están desactivados y no se pueden asignar. Son exclusivos del propietario.' }}</p>
                                @endif
                                @foreach($permisos as $permiso)
                                    @php
                                        $excepcion = $reglas->get($permiso->id);
                                        $decision = old('reglas.'.$permiso->id, $excepcion === null ? 'heredar' : ($excepcion->permitido ? 'permitir' : 'denegar'));
                                        $bloqueado = $modulo === 'usuarios';
                                        $protegido = $bloqueado || ($usuario->hasRole('propietario') && in_array($permiso->name, config('gestion_permisos.proteccion_propietario', []), true));
                                        $accion = explode('.', $permiso->name)[1];
                                        $id = 'permiso-individual-'.$permiso->id;
                                        $marcado = $bloqueado ? $usuario->hasRole('propietario') : ($protegido || $decision === 'permitir' || ($decision === 'heredar' && in_array($permiso->name, $heredados, true)));
                                    @endphp
                                    <div class="permission-option is-editable">
                                        <input type="hidden" id="decision-{{ $permiso->id }}" name="reglas[{{ $permiso->id }}]" value="{{ $protegido ? 'heredar' : $decision }}" data-regla-permiso>
                                        <div class="custom-control custom-checkbox mb-0">
                                            <input type="checkbox" class="custom-control-input" id="{{ $id }}" data-permiso="{{ $permiso->name }}" data-fijo="{{ $protegido && $marcado ? '1' : '0' }}" data-heredado="{{ in_array($permiso->name, $heredados, true) ? '1' : '0' }}" @checked($marcado) @disabled($protegido)>
                                            <label class="custom-control-label" for="{{ $id }}" @if($protegido) title="{{ $bloqueado ? 'Exclusivo del propietario' : 'Acceso de recuperación del propietario' }}" @endif><i class="fas {{ $protegido ? 'fa-lock' : 'fa-user-shield' }} field-label-icon" aria-hidden="true"></i>{{ config('gestion_permisos.acciones.'.$accion, ucfirst($accion)) }}</label>
                                        </div>
                                        @error('reglas.'.$permiso->id)<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                @endforeach
            </div>
            <div class="form-actions permissions-actions">
                <a class="btn btn-return" href="{{ route('admin.usuarios.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver a usuarios</a>
                <button class="btn btn-secondary" type="button" id="usar-permisos-rol"><i class="fas fa-undo mr-2" aria-hidden="true"></i>Usar permisos del rol</button>
                <button class="btn btn-success" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>Guardar permisos individuales</button>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script>
        const formularioPermisos = document.getElementById('permisos-individuales');

        formularioPermisos.addEventListener('change', function (event) {
            const casilla = event.target;
            if (!casilla.matches('input[type="checkbox"][data-permiso]') || casilla.disabled) return;

            const decision = casilla.closest('.permission-option').querySelector('[data-regla-permiso]');
            decision.value = casilla.checked ? 'permitir' : 'denegar';
        });

        document.getElementById('usar-permisos-rol').addEventListener('click', function () {
            formularioPermisos.querySelectorAll('input[type="checkbox"][data-permiso]').forEach(function (casilla) {
                casilla.checked = casilla.disabled ? casilla.dataset.fijo === '1' : casilla.dataset.heredado === '1';
                casilla.closest('.permission-option').querySelector('[data-regla-permiso]').value = 'heredar';
            });
        });
    </script>
@stop
