<?php

namespace App\Policies;

use App\Models\Cotizacion;
use App\Models\User;
use App\Support\Acceso;

class CotizacionPolicy
{
    public function viewAny(User $user): bool
    {
        return Acceso::permite($user, 'cotizaciones.consultar');
    }

    public function view(User $user, Cotizacion $cotizacion): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return Acceso::permite($user, 'cotizaciones.crear');
    }

    public function update(User $user, Cotizacion $cotizacion): bool
    {
        return $cotizacion->estado !== 'rechazada' && $this->contextoPedido($user, $cotizacion) && Acceso::permite($user, 'cotizaciones.modificar')
            && ($cotizacion->aprobado_at === null || $cotizacion->pedido !== null);
    }

    public function validatePrice(User $user, Cotizacion $cotizacion): bool
    {
        return $this->contextoPedido($user, $cotizacion) && Acceso::permite($user, 'cotizaciones.validar')
            && $cotizacion->aprobado_at === null;
    }

    public function approve(User $user, Cotizacion $cotizacion): bool
    {
        return $this->contextoPedido($user, $cotizacion) && Acceso::permite($user, 'cotizaciones.aprobar')
            && $cotizacion->aprobado_at === null;
    }

    public function delete(User $user, Cotizacion $cotizacion): bool
    {
        return Acceso::permite($user, 'cotizaciones.eliminar');
    }

    public function reject(User $user, Cotizacion $cotizacion): bool
    {
        return Acceso::permite($user, 'cotizaciones.rechazar')
            && in_array($cotizacion->estado, ['borrador', 'pendiente_costos', 'aprobada'], true)
            && ! $cotizacion->pedido()->exists();
    }

    private function contextoPedido(User $user, Cotizacion $cotizacion): bool
    {
        return ! $cotizacion->pedido || $user->can('update', $cotizacion->pedido);
    }
}
