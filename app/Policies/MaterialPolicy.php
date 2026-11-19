<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;
use App\Support\Acceso;

class MaterialPolicy
{
    public function viewAny(User $u): bool
    {
        return Acceso::permite($u, 'materiales.consultar');
    }

    public function view(User $u, Material $m): bool
    {
        return $this->viewAny($u);
    }

    public function create(User $u): bool
    {
        return Acceso::permite($u, 'materiales.crear');
    }

    public function update(User $u, Material $m): bool
    {
        return Acceso::permite($u, 'materiales.modificar');
    }

    public function cantidades(User $usuario, Material $material): bool
    {
        return $material->activo && Acceso::permite($usuario, 'materiales.modificar')
            && Acceso::permite($usuario, 'inventario.gestionar')
            && Acceso::permite($usuario, 'inventario.ajustes.crear');
    }

    public function delete(User $usuario, Material $registro): bool
    {
        return Acceso::permite($usuario, 'materiales.eliminar');
    }
}
