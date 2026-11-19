<?php

namespace App\Http\Middleware;

use App\Support\Acceso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExigirPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        if ($request->user() && ! $request->user()->fresh()->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'La cuenta está inactiva.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'Tu cuenta está inactiva. Contacta al propietario.']);
        }
        abort_unless(Acceso::permite($request->user(), $permiso), 403);

        return $next($request);
    }
}
