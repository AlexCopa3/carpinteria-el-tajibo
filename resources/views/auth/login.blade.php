@extends('layouts.auth')
@section('form')
    <h2>Iniciar sesión</h2><p class="text-muted mb-4">Ingresa con la cuenta que te asignó el propietario.</p>
    <x-alerts />
    <form method="POST" action="{{ route('login') }}">
        <div class="row form-fields">

            @csrf
            <div class="col-12 col-sm-6">
                <div class="form-group"><label for="email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo electrónico</label><input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="username" autofocus placeholder="tu.correo@ejemplo.com" maxlength="255" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<span id="email-error" class="invalid-feedback">{{ $message }}</span>@enderror</div>
            </div>
            <div class="col-12 col-sm-6">
                <div class="form-group"><label for="password"><i class="fas fa-lock field-label-icon" aria-hidden="true"></i>Contraseña</label><div class="password-wrap"><input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button type="button" class="password-toggle" data-password-toggle="password" aria-label="Mostrar contraseña" aria-pressed="false"><i class="far fa-eye" aria-hidden="true"></i></button></div>@error('password')<small id="password-error" class="text-danger">{{ $message }}</small>@enderror</div>
            </div>
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}><label class="custom-control-label" for="remember"><i class="fas fa-bookmark field-label-icon" aria-hidden="true"></i>Recordarme</label></div>@if(Route::has('password.request'))<a href="{{ route('password.request') }}" class="auth-link">¿Olvidaste tu contraseña?</a>@endif</div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block btn-auth">Entrar al panel <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i></button>
            </div>

        </div>
    </form>
    <p class="auth-help"><i class="fas fa-lock mr-2" aria-hidden="true"></i>Acceso exclusivo para el equipo de El Tajibo.</p>
    @unless(Route::has('password.request'))<p class="small text-muted">Si necesitas recuperar el acceso, contacta al propietario. La recuperación por correo aún no está habilitada.</p>@endunless
@stop
