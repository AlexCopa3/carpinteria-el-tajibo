<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermisosRolService
{
    public function modificar(User $actor, Role $rol, array $permisos): void
    {
        DB::transaction(function () use ($actor, $rol, $permisos) {
            $rol = Role::whereKey($rol->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $rol);
            $permitidos = \Spatie\Permission\Models\Permission::where('guard_name', 'web')->pluck('name')->all();
            \Illuminate\Support\Facades\Validator::make(['permisos' => $permisos], [
                'permisos' => ['array'],
                'permisos.*' => ['required', 'string', 'distinct', \Illuminate\Validation\Rule::in($permitidos)],
            ])->validate();
            $usuarios = array_values(array_filter($permitidos, fn ($permiso) => str_starts_with($permiso, 'usuarios.')));
            $recibidos = array_values(array_intersect($permisos, $usuarios));
            $fijos = $rol->name === 'propietario' ? $usuarios : [];
            sort($recibidos);
            sort($fijos);
            if ($recibidos !== $fijos) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'permisos' => 'Los permisos de Usuarios están bloqueados y son exclusivos del propietario.',
                ]);
            }
            $antes = $rol->permissions()->orderBy('name')->pluck('name')->all();
            $despues = array_values(array_unique($permisos));
            sort($despues);
            if ($antes === $despues) {
                return;
            }
            $rol->syncPermissions($despues);
            app(AuditoriaService::class)->registrar($actor, $rol, 'permisos_actualizados', ['permisos' => $antes], ['permisos' => $despues]);
        }, 3);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $actor->unsetRelation('roles')->unsetRelation('permissions');
    }
}
