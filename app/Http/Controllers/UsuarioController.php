<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    /**
     * Mostrar el listado de registros.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::with('roles')->latest('id')->paginate(12);

        return view('admin.usuarios.index', compact('usuarios'));
    }

    /**
     * Mostrar el formulario para registrar.
     */
    public function create()
    {
        $this->authorize('create', User::class);

        $existePropietario = User::role('propietario')->exists();

        return view('admin.usuarios.create', compact('existePropietario'));
    }

    /**
     * Guardar el registro validado.
     */
    public function store(Request $request, UsuarioService $usuarioService)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $request->merge([
            'tipo_identificacion' => is_string($request->tipo_identificacion) ? mb_strtolower(trim($request->tipo_identificacion)) : $request->tipo_identificacion,
            'numero_identificacion' => is_string($request->numero_identificacion) ? mb_strtoupper(trim($request->numero_identificacion)) : $request->numero_identificacion,
        ]);
        $this->authorize('create', User::class);
        $existePropietario = User::role('propietario')->exists();
        $rolesDisponibles = $existePropietario ? ['maestro'] : ['propietario', 'maestro'];
        $datos = $request->validate([
            'activo' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'tipo_identificacion' => ['required', Rule::in(['ci', 'pasaporte'])],
            'numero_identificacion' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D', Rule::unique('personal', 'numero_identificacion')->where('tipo_identificacion', $request->tipo_identificacion)],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'password' => ['prohibited'],
            'password_temporal' => ['prohibited'],
            'rol' => ['required', Rule::in($rolesDisponibles)],
            'permissions' => ['prohibited'],
            'roles' => ['prohibited'],
        ]);

        $usuarioService->crear($request->user(), $datos);

        return redirect()->route('admin.usuarios.index')->with([
            'mensaje' => 'La cuenta se creó correctamente.',
            'icono' => 'success',
        ]);
    }

    /**
     * Mostrar los datos del registro.
     */
    public function show(User $usuario)
    {
        $this->authorize('view', $usuario);
        $usuario->load(['roles', 'personal']);

        return view('admin.usuarios.show', compact('usuario'));
    }

    /**
     * Mostrar el formulario para editar.
     */
    public function edit(User $usuario)
    {
        $this->authorize('update', $usuario);

        $usuario->load('personal');

        $existePropietario = User::role('propietario')->where('id', '!=', $usuario->id)->exists();

        return view('admin.usuarios.edit', compact('usuario', 'existePropietario'));
    }

    /**
     * Actualizar el registro validado.
     */
    public function update(Request $request, User $usuario, UsuarioService $usuarioService)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        if ($request->has('tipo_identificacion') && is_string($request->tipo_identificacion)) {
            $request->merge(['tipo_identificacion' => mb_strtolower(trim($request->tipo_identificacion))]);
        }
        if ($request->has('numero_identificacion') && is_string($request->numero_identificacion)) {
            $request->merge(['numero_identificacion' => mb_strtoupper(trim($request->numero_identificacion))]);
        }
        $this->authorize('update', $usuario);
        $existePropietario = User::role('propietario')->where('id', '!=', $usuario->id)->exists();
        $rolesDisponibles = ! $existePropietario || $usuario->hasRole('propietario') ? ['propietario', 'maestro'] : ['maestro'];
        $datos = $request->validate([
            'activo' => ['sometimes', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'tipo_identificacion' => ['nullable', 'required_with:numero_identificacion', Rule::in(['ci', 'pasaporte'])],
            'numero_identificacion' => ['nullable', 'required_with:tipo_identificacion', 'string', 'max:30', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D', Rule::unique('personal', 'numero_identificacion')->where('tipo_identificacion', $request->tipo_identificacion)->ignore($usuario->personal?->id)],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'password' => ['prohibited'],
            'password_temporal' => ['prohibited'],
            'rol' => ['required', Rule::in($rolesDisponibles)],
            'permissions' => ['prohibited'],
            'roles' => ['prohibited'],
        ]);

        $usuarioService->modificar($request->user(), $usuario, $datos);

        return redirect()->route('admin.usuarios.index')->with([
            'mensaje' => 'La cuenta se actualizó correctamente.',
            'icono' => 'success',
        ]);
    }
}
