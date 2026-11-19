<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Acceso;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $usuario): bool
    {
        return Acceso::permite($usuario, 'usuarios.consultar') && Acceso::permite($usuario, 'usuarios.gestionar') && Acceso::permite($usuario, 'usuarios.asignar_roles');
    }

    public function update(User $usuario, Role $rol): bool
    {
        return $this->viewAny($usuario) && $rol->guard_name === 'web' && in_array($rol->name, ['propietario', 'maestro'], true);
    }
}
