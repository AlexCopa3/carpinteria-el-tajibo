<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermisosRolRequest;
use App\Services\PermisosRolService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Role::class);
        $roles = Role::where('guard_name', 'web')
            ->whereIn('name', ['propietario', 'maestro'])
            ->withCount('permissions')->latest('id')->get();

        return view('admin.permisos.index', compact('roles'));
    }

    public function edit(Role $rol)
    {
        $this->authorize('update', $rol);
        $seleccionados = $rol->permissions()->pluck('name')->all();
        $grupos = Permission::where('guard_name', 'web')->orderBy('name')->pluck('name')
            ->groupBy(fn ($permiso) => explode('.', $permiso)[0]);

        return view('admin.permisos.edit', compact('rol', 'seleccionados', 'grupos'));
    }

    public function update(PermisosRolRequest $request, Role $rol, PermisosRolService $permisosRolService)
    {
        $permisosRolService->modificar($request->user(), $rol, $request->validated()['permisos']);

        return redirect()->route('admin.permisos.edit', $rol)->with([
            'mensaje' => 'Permisos del rol actualizados. El cambio se aplica a sus usuarios.',
            'icono' => 'success',
        ]);
    }
}
