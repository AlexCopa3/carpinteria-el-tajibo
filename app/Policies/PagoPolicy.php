<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Support\Facades\Schema;

class PagoPolicy
{
    public function viewAny(User $usuario): bool
    {
        return Acceso::permite($usuario, 'pagos.consultar')
            && Acceso::permite($usuario, 'pedidos.consultar')
            && Schema::hasTable('pagos');
    }

    public function view(User $usuario, Pago $pago): bool
    {
        return $this->viewAny($usuario);
    }

    public function create(User $usuario): bool
    {
        return $this->viewAny($usuario) && Acceso::permite($usuario, 'pagos.gestionar');
    }

    public function update(User $usuario, Pago $pago): bool
    {
        return $this->create($usuario);
    }
}
