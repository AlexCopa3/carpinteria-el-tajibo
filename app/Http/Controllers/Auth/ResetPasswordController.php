<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use App\Support\ReglasContrasena;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/admin';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function credentials(Request $request): array
    {
        return $request->only('email', 'password', 'password_confirmation', 'token') + ['activo' => true];
    }

    protected function resetPassword($usuario, $password): void
    {
        DB::transaction(function () use ($usuario, $password) {
            $this->setUserPassword($usuario, $password);
            $usuario->setRememberToken(Str::random(60));
            $usuario->save();
            app(AuditoriaService::class)->registrar($usuario, $usuario, 'contrasena_restablecida', [], ['seguridad' => 'Contraseña restablecida por recuperación']);
        });
        event(new PasswordReset($usuario));
        $this->guard()->login($usuario);
    }

    protected function rules(): array
    {
        return ['token' => 'required', 'email' => 'required|email', 'password' => ReglasContrasena::reglas()];
    }
}
