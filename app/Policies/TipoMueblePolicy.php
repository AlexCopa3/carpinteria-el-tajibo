<?php

namespace App\Policies;

use App\Models\TipoMueble;
use App\Models\User;
use App\Support\Acceso;

class TipoMueblePolicy
{
    public function viewAny(User $usuario): bool
    {
        return Acceso::permite($usuario, 'cotizaciones.consultar');
    }

    public function view(User $usuario, TipoMueble $tipoMueble): bool
    {
        return $this->viewAny($usuario);
    }

    public function create(User $usuario): bool
    {
        return $this->viewAny($usuario) && Acceso::permite($usuario, 'configuracion.gestionar');
    }

    public function update(User $usuario, TipoMueble $tipoMueble): bool
    {
        return $this->create($usuario);
    }
}
