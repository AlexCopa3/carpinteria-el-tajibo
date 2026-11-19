@extends('layouts.auth')
@section('form')
    <h2>Recuperar acceso</h2><p class="text-muted mb-4">Te enviaremos un enlace para restablecer tu contraseña.</p><x-alerts />
    <form method="POST" action="{{ route('password.email') }}">
        <div class="row form-fields">
            @csrf<div class="col-12">
                <div class="form-group"><label for="email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo electrónico</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
            </div><div class="col-12">
                <button class="btn btn-primary btn-block btn-auth" type="submit">Enviar enlace</button>
            </div>
        </div>
    </form><a class="auth-link d-inline-block mt-3" href="{{ route('login') }}">Volver al inicio de sesión</a>
@stop
