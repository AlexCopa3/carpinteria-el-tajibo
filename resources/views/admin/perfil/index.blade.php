@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Mi perfil" eyebrow="MI CUENTA" subtitle="Tus datos personales y contraseña." />
@stop
@section('content')
    <x-alerts />
    @if($usuario->password_temporal)
        <div class="alert alert-warning" role="alert"><strong>Cambia tu contraseña inicial para continuar.</strong> Elige una nueva que solo tú conozcas.</div>
    @endif

    <div class="row profile-columns">
        @unless($usuario->password_temporal)
            <div class="col-12 col-lg-6 profile-data-column">
                <section class="card dashboard-card form-themed form-action-update profile-section">
                    <div class="card-header">
                        <span class="profile-section-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                        <div class="profile-section-heading">
                            <h2 class="card-title">Datos personales y de usuario</h2>
                            <p>Tu información personal y de acceso</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="perfil-datos" method="POST" action="{{ route('admin.perfil.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="row form-fields">
                                <div class="col-12">
                                    <div class="form-group">
                                        <div class="form-label-line"><label for="name"><i class="fas fa-user field-label-icon" aria-hidden="true"></i>Nombre completo</label><span class="form-required" aria-hidden="true">*</span></div>
                                        <input id="name" name="name" type="text" value="{{ old('name', $usuario->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                                        @error('name')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_identificacion"><i class="fas fa-id-card field-label-icon" aria-hidden="true"></i>Tipo de identificación</label>
                                        <select id="tipo_identificacion" name="tipo_identificacion" class="form-control @error('tipo_identificacion') is-invalid @enderror">
                                            <option value="">Selecciona un tipo</option>
                                            <option value="ci" @selected(old('tipo_identificacion', $usuario->personal?->tipo_identificacion) === 'ci')>Cédula de identidad</option>
                                            <option value="pasaporte" @selected(old('tipo_identificacion', $usuario->personal?->tipo_identificacion) === 'pasaporte')>Pasaporte</option>
                                        </select>
                                        @error('tipo_identificacion')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="numero_identificacion"><i class="fas fa-id-badge field-label-icon" aria-hidden="true"></i>Número de identificación</label>
                                        <input id="numero_identificacion" name="numero_identificacion" type="text" value="{{ old('numero_identificacion', $usuario->personal?->numero_identificacion ?? '') }}" class="form-control @error('numero_identificacion') is-invalid @enderror" maxlength="30">
                                        @error('numero_identificacion')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="telefono"><i class="fas fa-phone field-label-icon" aria-hidden="true"></i>Teléfono (opcional)</label>
                                        <input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $usuario->personal?->telefono ?? '') }}" class="form-control @error('telefono') is-invalid @enderror" maxlength="50" >
                                        @error('telefono')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="direccion"><i class="fas fa-map-marker-alt field-label-icon" aria-hidden="true"></i>Dirección (opcional)</label>
                                        <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $usuario->personal?->direccion ?? '') }}" class="form-control @error('direccion') is-invalid @enderror" maxlength="255" >
                                        @error('direccion')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="profile-account-divider">
                                <i class="fas fa-user-circle" aria-hidden="true"></i>
                                <h3>Datos de usuario</h3>
                            </div>
                            <div class="row form-fields">
                                <div class="col-12">
                                    <div class="form-group">
                                        <div class="form-label-line"><label for="email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo electrónico</label><span class="form-required" aria-hidden="true">*</span></div>
                                        <input form="perfil-datos" id="email" name="email" type="email" value="{{ old('email', $usuario->email ?? '') }}" class="form-control @error('email') is-invalid @enderror" maxlength="255" required>
                                        @error('email')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                            <dl class="record-fields record-fields-boxed mb-3">
                                <div class="record-field">
                                    <dt><i class="fas fa-user-tag record-value-icon" aria-hidden="true"></i>Rol</dt>
                                    <dd>{{ $usuario->getRoleNames()->map(fn ($rol) => $rol === 'propietario' ? 'Propietario' : ($rol === 'maestro' ? 'Maestro carpintero' : $rol))->join(', ') ?: 'Sin rol asignado' }}</dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-toggle-on record-value-icon" aria-hidden="true"></i>Estado</dt>
                                    <dd>{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</dd>
                                </div>
                            </dl>
                            <p class="form-text text-muted">El rol y el estado se administran desde Usuarios.</p>
                            <div class="form-actions">
                                <a class="btn btn-return" href="{{ route('admin.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver</a>
                                <button class="btn btn-success" type="submit" form="perfil-datos"><i class="fas fa-save mr-2" aria-hidden="true"></i>Actualizar perfil</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        @endunless

        <div class="col-12 col-lg-6 {{ $usuario->password_temporal ? 'mx-auto' : '' }} profile-password-column">
            <section class="card dashboard-card form-themed form-action-update profile-section">
                <div class="card-header">
                    <span class="profile-section-icon" aria-hidden="true"><i class="fas fa-key"></i></span>
                    <div class="profile-section-heading">
                        <h2 class="card-title">Cambiar contraseña</h2>
                        <p>Protege el acceso a tu cuenta</p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.perfil.password') }}">
                        @csrf
                        @method('PUT')
                        <p class="form-text text-muted">La nueva contraseña debe tener al menos 12 caracteres, mayúscula, minúscula, número y símbolo.</p>
                        <div class="row form-fields">
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="current_password"><i class="fas fa-lock field-label-icon" aria-hidden="true"></i>Contraseña actual</label><span class="form-required" aria-hidden="true">*</span></div>
                                    <input id="current_password" name="current_password" type="password" class="form-control @error('current_password', 'contrasena') is-invalid @enderror" required autocomplete="current-password" >
                                    @error('current_password', 'contrasena')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="password"><i class="fas fa-lock field-label-icon" aria-hidden="true"></i>Nueva contraseña</label><span class="form-required" aria-hidden="true">*</span></div>
                                    <input id="password" name="password" type="password" class="form-control @error('password', 'contrasena') is-invalid @enderror" required autocomplete="new-password" minlength="12" maxlength="72">
                                    @error('password', 'contrasena')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="password_confirmation"><i class="fas fa-lock field-label-icon" aria-hidden="true"></i>Repetir nueva contraseña</label><span class="form-required" aria-hidden="true">*</span></div>
                                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation', 'contrasena') is-invalid @enderror" required autocomplete="new-password" >
                                    @error('password_confirmation', 'contrasena')<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button class="btn btn-success" type="submit"><i class="fas fa-key mr-2" aria-hidden="true"></i>Actualizar contraseña</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@stop
