<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;
use App\Support\Acceso;

class PlanificacionMaterialPolicy
{
    public function viewAny(User $usuario): bool
    {
        return Acceso::permite($usuario, 'pedidos.consultar') && Acceso::permite($usuario, 'planificacion.consultar');
    }

    public function view(User $usuario, Pedido $pedido): bool
    {
        return $this->viewAny($usuario);
    }

    public function update(User $usuario, Pedido $pedido): bool
    {
        return $this->viewAny($usuario) && Acceso::permite($usuario, 'planificacion.modificar') && in_array($pedido->estado, ['aceptado', 'en_fabricacion'], true);
    }
}
