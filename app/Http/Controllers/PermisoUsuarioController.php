<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PermisosUsuarioService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermisoUsuarioController extends Controller
{
    public function edit(User $usuario)
    {
        $this->authorize('asignarPermisos', $usuario);
        abort_unless(\Illuminate\Support\Facades\Schema::hasTable('permisos_usuario'), 503, 'La actualización de permisos individuales está pendiente de aplicar.');
        $catalogo = Permission::where('guard_name', 'web')->orderBy('name')->get();
        $grupos = $catalogo->groupBy(fn ($permiso) => explode('.', $permiso->name)[0]);
        $reglas = $usuario->permisosIndividuales()->get()->keyBy('permission_id');
        $heredados = $usuario->getAllPermissions()->pluck('name')->all();

        return view('admin.permisos.usuario', compact('usuario', 'grupos', 'reglas', 'heredados'));
    }

    public function update(Request $request, User $usuario, PermisosUsuarioService $permisosUsuarioService)
    {
        $this->authorize('asignarPermisos', $usuario);
        $datos = $request->validate([
            'reglas' => ['present', 'array'],
            'reglas.*' => ['required', 'string', Rule::in(['heredar', 'permitir', 'denegar'])],
            'permisos' => ['prohibited'], 'id' => ['prohibited'], 'user_id' => ['prohibited'],
            'rol' => ['prohibited'], 'roles' => ['prohibited'], 'permissions' => ['prohibited'], 'activo' => ['prohibited'],
        ]);
        $permisosUsuarioService->modificar($request->user(), $usuario, $datos['reglas']);

        return redirect()->route('admin.usuarios.permisos.edit', $usuario)->with([
            'mensaje' => 'Permisos individuales actualizados. La decisión individual prevalece sobre el rol.',
            'icono' => 'success',
        ]);
    }
}
