<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Acceso;

class ClientePolicy
{
    public function viewAny(User $u): bool
    {
        return Acceso::permite($u, 'clientes.consultar');
    }

    public function view(User $u, Cliente $c): bool
    {
        return $this->viewAny($u);
    }

    public function create(User $u): bool
    {
        return Acceso::permite($u, 'clientes.crear');
    }

    public function update(User $u, Cliente $c): bool
    {
        return Acceso::permite($u, 'clientes.modificar');
    }

    public function deactivate(User $u, Cliente $c): bool
    {
        return Acceso::permite($u, 'clientes.desactivar');
    }

    public function delete(User $usuario, Cliente $registro): bool
    {
        return Acceso::permite($usuario, 'clientes.eliminar');
    }
}
