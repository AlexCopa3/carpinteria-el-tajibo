@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Editar cuenta" eyebrow="ADMINISTRACIÓN / USUARIOS" subtitle="Datos de la persona y acceso al sistema." />
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-12 col-xl-10 usuarios-form-column">
            <section class="card dashboard-card form-themed form-action-update">
                <div class="card-header"><h2 class="card-title">Actualizar usuario</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
                        @csrf
                        @method('PUT')
                        <div class="row form-fields">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="name"><i class="fas fa-user field-label-icon" aria-hidden="true"></i>Nombre completo</label><span class="form-required" aria-hidden="true">*</span></div>
                                    <input id="name" name="name" type="text" value="{{ old('name', $usuario->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                                    @error('name')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo electrónico</label><span class="form-required" aria-hidden="true">*</span></div>
                                    <input id="email" name="email" type="email" value="{{ old('email', $usuario->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" maxlength="255" required>
                                    @error('email')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
        
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="tipo_identificacion"><i class="fas fa-id-card field-label-icon" aria-hidden="true"></i>Tipo de identificación</label>
                                    <select id="tipo_identificacion" name="tipo_identificacion" class="form-control @error('tipo_identificacion') is-invalid @enderror" >
                                        <option value="">Selecciona un tipo</option>
                                        <option value="ci" @selected(old('tipo_identificacion', $usuario->personal?->tipo_identificacion ?? '') === 'ci')>CI</option>
                                        <option value="pasaporte" @selected(old('tipo_identificacion', $usuario->personal?->tipo_identificacion ?? '') === 'pasaporte')>Pasaporte</option>
                                    </select>
                                    @error('tipo_identificacion')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="numero_identificacion"><i class="fas fa-id-card field-label-icon" aria-hidden="true"></i>Número de identificación</label>
                                    <input id="numero_identificacion" name="numero_identificacion" value="{{ old('numero_identificacion', $usuario->personal?->numero_identificacion ?? '') }}" class="form-control @error('numero_identificacion') is-invalid @enderror" maxlength="30"  aria-describedby="identificacion-help">
                                    <small id="identificacion-help" class="form-text text-muted">Completa ambos datos si añades o cambias la identificación. Cambiarla no restablece la contraseña.</small>
                                    @error('numero_identificacion')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="telefono"><i class="fas fa-phone field-label-icon" aria-hidden="true"></i>Teléfono (opcional)</label>
                                    <input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $usuario->personal?->telefono ?? '') }}" class="form-control @error('telefono') is-invalid @enderror" maxlength="50" >
                                    @error('telefono')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="direccion"><i class="fas fa-map-marker-alt field-label-icon" aria-hidden="true"></i>Dirección (opcional)</label>
                                    <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $usuario->personal?->direccion ?? '') }}" class="form-control @error('direccion') is-invalid @enderror" maxlength="255" >
                                    @error('direccion')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
        
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="rol"><i class="fas fa-user-shield field-label-icon" aria-hidden="true"></i>Rol de acceso</label><span class="form-required" aria-hidden="true">*</span></div>
                                    <select id="rol" name="rol" class="form-control @error('rol') is-invalid @enderror" required>
                                        <option value="maestro" @selected(old('rol', isset($usuario) ? $usuario->getRoleNames()->first() : 'maestro') === 'maestro')>Maestro carpintero</option>
                                        @if(!$existePropietario || $usuario->hasRole('propietario'))
                                            <option value="propietario" @selected(old('rol', isset($usuario) ? $usuario->getRoleNames()->first() : 'maestro') === 'propietario')>Propietario</option>
                                        @endif
                                    </select>
                                    @error('rol')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="activo"><i class="fas fa-toggle-on field-label-icon" aria-hidden="true"></i>Estado</label>
                                    <select id="activo" name="activo" class="form-control @error('activo') is-invalid @enderror" required>
                                        <option value="1" @selected(old('activo', (int) $usuario->activo) == 1)>Activo</option>
                                        <option value="0" @selected(old('activo', (int) $usuario->activo) == 0)>Inactivo</option>
                                    </select>
                                    @error('activo')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
        
                        </div>
                        <div class="form-actions">
                            <a class="btn btn-cancel" href="{{ route('admin.usuarios.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                            <button class="btn btn-success" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@stop
