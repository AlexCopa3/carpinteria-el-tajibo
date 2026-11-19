<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Support\ReglasContrasena;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user()->fresh(['personal']);

        return view('admin.perfil.index', compact('usuario'));
    }

    public function update(Request $request)
    {
        if (is_string($request->email)) {
            $request->merge(['email' => mb_strtolower(trim($request->email))]);
        }
        if ($request->has('tipo_identificacion') && is_string($request->tipo_identificacion)) {
            $request->merge(['tipo_identificacion' => mb_strtolower(trim($request->tipo_identificacion))]);
        }
        if ($request->has('numero_identificacion') && is_string($request->numero_identificacion)) {
            $request->merge(['numero_identificacion' => mb_strtoupper(trim($request->numero_identificacion))]);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'id' => 'prohibited', 'rol' => 'prohibited', 'roles' => 'prohibited', 'permissions' => 'prohibited',
            'activo' => 'prohibited',
            'tipo_identificacion' => ['nullable', 'required_with:numero_identificacion', Rule::in(['ci', 'pasaporte'])],
            'numero_identificacion' => ['nullable', 'required_with:tipo_identificacion', 'string', 'max:30', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D', Rule::unique('personal', 'numero_identificacion')->where('tipo_identificacion', $request->tipo_identificacion)->ignore($request->user()->personal?->id)],
            'password' => 'prohibited', 'password_temporal' => 'prohibited',
        ]);
        DB::transaction(function () use ($request) {
            $usuario = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($usuario->activo && ! $usuario->password_temporal, 403);
            $auditoria = app(AuditoriaService::class);
            $personal = Personal::where('user_id', $usuario->id)->lockForUpdate()->first() ?: new Personal;
            $personal->user_id = $usuario->id;
            $antes = $auditoria->datos($usuario) + $personal->only(['tipo_identificacion', 'numero_identificacion', 'telefono', 'direccion']);
            $usuario->name = $request->name;
            if ($usuario->email !== $request->email) {
                $usuario->email_verified_at = null;
            }
            $usuario->email = $request->email;
            if ($request->has('tipo_identificacion')) {
                $personal->tipo_identificacion = $request->tipo_identificacion;
            }
            if ($request->has('numero_identificacion')) {
                $personal->numero_identificacion = $request->numero_identificacion;
            }
            $personal->telefono = $request->telefono;
            $personal->direccion = $request->direccion;
            $usuario->save();
            $personal->save();
            $auditoria->registrar($usuario, $usuario, 'modificado', $antes, $auditoria->datos($usuario) + $personal->only(['tipo_identificacion', 'numero_identificacion', 'telefono', 'direccion']));
        });

        return redirect()->route('admin.perfil.index')->with(['mensaje' => 'Tu perfil se actualizó correctamente.', 'icono' => 'success']);
    }

    public function password(Request $request)
    {
        $request->validateWithBag('contrasena', [
            'current_password' => ['required', 'current_password'],
            'password' => [...ReglasContrasena::reglas(), 'different:current_password'],
            'rol' => 'prohibited', 'roles' => 'prohibited', 'permissions' => 'prohibited', 'activo' => 'prohibited',
            'id' => 'prohibited', 'password_temporal' => 'prohibited',
        ], ['current_password.current_password' => 'La contraseña actual no es correcta.']);
        DB::transaction(function () use ($request) {
            $usuario = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($usuario->activo, 403);
            if (! Hash::check($request->current_password, $usuario->password)) {
                throw ValidationException::withMessages(['current_password' => 'La contraseña actual cambió. Recarga e inténtalo de nuevo.']);
            }
            $usuario->password = $request->password;
            $usuario->password_temporal = false;
            $usuario->remember_token = Str::random(60);
            $usuario->save();
            app(AuditoriaService::class)->registrar($usuario, $usuario, 'contrasena_actualizada', [], ['descripcion' => 'El usuario actualizó su contraseña.']);
            if (config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $usuario->id)->where('id', '!=', $request->session()->getId())->delete();
            }
        });
        Auth::setUser($request->user()->fresh());
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.perfil.index')->with(['mensaje' => 'Tu contraseña se actualizó correctamente.', 'icono' => 'success']);
    }
}
