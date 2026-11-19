<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;
use App\Support\Acceso;

class CategoriaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return Acceso::permite($usuario, 'materiales.consultar');
    }

    public function view(User $usuarioActual, Categoria $categoria): bool
    {
        return $this->viewAny($usuarioActual);
    }

    public function create(User $usuario): bool
    {
        return Acceso::permite($usuario, 'categorias.crear');
    }

    public function update(User $usuario, Categoria $categoria): bool
    {
        return Acceso::permite($usuario, 'categorias.modificar');
    }

    public function delete(User $usuario, Categoria $registro): bool
    {
        return Acceso::permite($usuario, 'categorias.eliminar');
    }
}
