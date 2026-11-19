<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/admin';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function validateLogin(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);
    }

    protected function credentials(Request $request): array
    {
        return ['email' => $request->input('email'), 'password' => $request->input('password'), 'activo' => true];
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->password_temporal) {
            return redirect()->route('admin.perfil.index');
        }
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('login')->with('status', 'Has cerrado la sesión de forma segura.');
    }
}
