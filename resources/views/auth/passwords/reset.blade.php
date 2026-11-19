@extends('layouts.auth')
@section('form')
    <h2>Nueva contraseña</h2><p class="text-muted mb-4">Usa al menos 12 caracteres, mayúsculas, minúsculas, números y símbolos.</p><x-alerts />
    <form method="POST" action="{{ route('password.update') }}">
        <div class="row form-fields">
            @csrf<input type="hidden" name="token" value="{{ $token }}"><div class="col-12">
                <div class="form-group"><label for="email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo electrónico</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></div>
            </div><div class="col-12 col-sm-6">
                <div class="form-group"><label for="password"><i class="fas fa-lock field-label-icon" aria-hidden="true"></i>Contraseña</label><input class="form-control" id="password" type="password" name="password" required autocomplete="new-password"></div>
            </div><div class="col-12 col-sm-6">
                <div class="form-group"><label for="password_confirmation"><i class="fas fa-lock field-label-icon" aria-hidden="true"></i>Repetir contraseña</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
            </div><div class="col-12">
                <button class="btn btn-primary btn-block btn-auth" type="submit">Guardar contraseña</button>
            </div>
        </div>
    </form>
@stop
