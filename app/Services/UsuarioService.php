<?php

namespace App\Services;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UsuarioService
{
    public function crear(User $actor, array $datos): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        Validator::make($datos, [
            'tipo_identificacion' => 'required|in:ci,pasaporte',
            'numero_identificacion' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D'],
        ])->validate();

        return DB::transaction(function () use ($actor, $datos): User {
            if ($datos['rol'] === 'propietario' && User::role('propietario')->exists()) {
                throw ValidationException::withMessages(['rol' => 'Ya existe una cuenta de propietario.']);
            }
            $usuario = new User;
            $usuario->name = $datos['name'];
            $usuario->email = $datos['email'];
            $usuario->password = 'tj.'.$datos['numero_identificacion'];
            $usuario->password_temporal = true;
            $usuario->activo = true;
            $usuario->save();
            $personal = new Personal;
            $personal->user_id = $usuario->id;
            $personal->tipo_identificacion = $datos['tipo_identificacion'];
            $personal->numero_identificacion = $datos['numero_identificacion'];
            $personal->telefono = $datos['telefono'] ?? null;
            $personal->direccion = $datos['direccion'] ?? null;
            $personal->save();
            $usuario->setRelation('personal', $personal);
            $usuario->assignRole($datos['rol']);
            app(AuditoriaService::class)->registrar($actor, $usuario, 'creado', [], app(AuditoriaService::class)->datos($usuario) + $personal->only(['tipo_identificacion', 'numero_identificacion', 'telefono', 'direccion']) + ['rol' => $datos['rol']]);

            return $usuario;
        });
    }

    public function modificar(User $actor, User $usuario, array $datos): void
    {
        DB::transaction(function () use ($actor, $usuario, $datos): void {
            $propietarios = User::role('propietario')->where('activo', true)->orderBy('id')->lockForUpdate()->get();
            $usuario = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $usuario);
            if ($datos['rol'] === 'propietario' && ! $usuario->hasRole('propietario')
                && User::role('propietario')->where('id', '!=', $usuario->id)->exists()) {
                throw ValidationException::withMessages(['rol' => 'Ya existe una cuenta de propietario.']);
            }
            $activo = array_key_exists('activo', $datos) ? (bool) $datos['activo'] : $usuario->activo;
            if ($actor->is($usuario) && ! $activo) {
                throw ValidationException::withMessages(['activo' => 'No puedes desactivar tu propia cuenta.']);
            }
            if ($usuario->hasRole('propietario') && ($datos['rol'] !== 'propietario' || ! $activo)
                && ($actor->is($usuario) || ($usuario->activo && $propietarios->count() <= 1))) {
                throw ValidationException::withMessages(['rol' => 'No puedes retirar tu propio acceso de propietario ni dejar el sistema sin propietario activo.']);
            }
            $auditoria = app(AuditoriaService::class);
            $personal = Personal::where('user_id', $usuario->id)->lockForUpdate()->first() ?: new Personal;
            $personal->user_id = $usuario->id;
            $antes = $auditoria->datos($usuario) + $personal->only(['tipo_identificacion', 'numero_identificacion', 'telefono', 'direccion']) + ['rol' => $usuario->getRoleNames()->first()];
            $usuario->name = $datos['name'];
            $usuario->email = $datos['email'];
            $usuario->activo = $activo;
            if (array_key_exists('tipo_identificacion', $datos)) {
                $personal->tipo_identificacion = $datos['tipo_identificacion'];
            }
            if (array_key_exists('numero_identificacion', $datos)) {
                $personal->numero_identificacion = $datos['numero_identificacion'];
            }
            if (array_key_exists('telefono', $datos)) {
                $personal->telefono = $datos['telefono'];
            }
            if (array_key_exists('direccion', $datos)) {
                $personal->direccion = $datos['direccion'];
            }
            $usuario->save();
            $personal->save();
            $usuario->setRelation('personal', $personal);
            $usuario->syncRoles([$datos['rol']]);
            $auditoria->registrar($actor, $usuario, 'modificado', $antes, $auditoria->datos($usuario) + $personal->only(['tipo_identificacion', 'numero_identificacion', 'telefono', 'direccion']) + ['rol' => $datos['rol']]);
        });
    }
}
