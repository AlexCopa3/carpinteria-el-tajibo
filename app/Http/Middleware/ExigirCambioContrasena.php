<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExigirCambioContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user()?->fresh();
        if ($request->user() && (! $usuario || ! $usuario->activo)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'La cuenta está inactiva.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'Tu cuenta está inactiva. Contacta al propietario.']);
        }
        if ($usuario?->password_temporal && ! $request->routeIs('admin.perfil.index', 'admin.perfil.password', 'logout', 'admin.ajustes.logo')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Debes cambiar tu contraseña inicial desde Mi perfil.'], 403)
                : redirect()->route('admin.perfil.index')->with('status', 'Cambia tu contraseña inicial para continuar.');
        }

        return $next($request);
    }
}
