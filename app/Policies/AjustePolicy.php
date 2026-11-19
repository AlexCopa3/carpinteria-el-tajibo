<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Acceso;

class AjustePolicy
{
    public function gestionar(User $usuario): bool
    {
        return Acceso::permite($usuario, 'configuracion.gestionar');
    }
}
