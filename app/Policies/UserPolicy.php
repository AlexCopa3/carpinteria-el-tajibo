<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Acceso;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return Acceso::permite($user, 'usuarios.gestionar') && Acceso::permite($user, 'usuarios.consultar');
    }

    public function view(User $usuarioActual, User $usuario): bool
    {
        return $this->viewAny($usuarioActual);
    }

    public function create(User $user): bool
    {
        return Acceso::permite($user, 'usuarios.gestionar') && Acceso::permite($user, 'usuarios.crear')
            && Acceso::permite($user, 'usuarios.asignar_roles');
    }

    public function update(User $user, User $usuario): bool
    {
        return Acceso::permite($user, 'usuarios.gestionar') && Acceso::permite($user, 'usuarios.modificar')
            && Acceso::permite($user, 'usuarios.asignar_roles');
    }

    public function asignarPermisos(User $actor, User $usuario): bool
    {
        return $this->viewAny($actor) && Acceso::permite($actor, 'usuarios.asignar_roles');
    }
}
