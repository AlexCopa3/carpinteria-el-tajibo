<?php

namespace App\Services;

use App\Models\User;
use App\Models\PermisoUsuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

class PermisosUsuarioService
{
    public function modificar(User $actor, User $usuario, array $reglas): void
    {
        DB::transaction(function () use ($actor, $usuario, $reglas) {
            $usuario = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('asignarPermisos', $usuario);
            Validator::make(['reglas' => $reglas], [
                'reglas' => ['array'],
                'reglas.*' => ['required', 'string', Rule::in(['heredar', 'permitir', 'denegar'])],
            ])->validate();
            $catalogo = Permission::where('guard_name', 'web')->get()->keyBy('id');
            foreach ($reglas as $id => $decision) {
                $permiso = $catalogo->get($id);
                if (! $permiso) {
                    throw ValidationException::withMessages(['reglas' => 'La selección contiene un permiso inexistente o de otro acceso.']);
                }
                if (str_starts_with($permiso->name, 'usuarios.') && $decision !== 'heredar') {
                    throw ValidationException::withMessages(['reglas' => 'Los permisos de Usuarios están bloqueados y son exclusivos del propietario.']);
                }
                if ($decision === 'denegar' && $usuario->hasRole('propietario', 'web')
                    && in_array($permiso->name, config('gestion_permisos.proteccion_propietario', []), true)) {
                    throw ValidationException::withMessages(['reglas' => 'El propietario conserva el acceso de recuperación a la gestión de permisos.']);
                }
            }
            $antes = $usuario->permisosIndividuales()->orderBy('permission_id')->get(['permission_id', 'permitido'])->toArray();
            foreach ($reglas as $id => $decision) {
                if ($decision === 'heredar') {
                    $usuario->permisosIndividuales()->where('permission_id', $id)->delete();
                    continue;
                }
                $excepcion = $usuario->permisosIndividuales()->where('permission_id', $id)->first() ?: new PermisoUsuario;
                $excepcion->user_id = $usuario->id;
                $excepcion->permission_id = $id;
                $excepcion->permitido = $decision === 'permitir';
                $excepcion->save();
            }
            $despues = $usuario->permisosIndividuales()->orderBy('permission_id')->get(['permission_id', 'permitido'])->toArray();
            if ($antes !== $despues) {
                app(AuditoriaService::class)->registrar($actor, $usuario, 'permisos_actualizados',
                    ['excepciones' => $antes], ['excepciones' => $despues]);
            }
        }, 3);
        $usuario->unsetRelation('permisosIndividuales');
    }
}
