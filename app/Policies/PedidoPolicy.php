<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;
use App\Support\Acceso;

class PedidoPolicy
{
    public function viewAny(User $usuario): bool
    {
        return Acceso::permite($usuario, 'pedidos.consultar');
    }

    public function view(User $usuario, Pedido $pedido): bool
    {
        return $this->viewAny($usuario);
    }

    public function verUsoMateriales(User $usuario, Pedido $pedido): bool
    {
        return $this->view($usuario, $pedido) && Acceso::permite($usuario, 'planificacion.consultar');
    }

    public function registrarUsoMateriales(User $usuario, Pedido $pedido): bool
    {
        return $this->verUsoMateriales($usuario, $pedido)
            && Acceso::permite($usuario, 'pedidos.modificar')
            && Acceso::permite($usuario, 'planificacion.modificar')
            && $pedido->estado === 'en_fabricacion';
    }

    public function validarMateriales(User $usuario, Pedido $pedido): bool
    {
        return $this->verUsoMateriales($usuario, $pedido)
            && Acceso::permite($usuario, 'pedidos.modificar')
            && Acceso::permite($usuario, 'planificacion.modificar')
            && Acceso::permite($usuario, 'materiales.validar');
    }

    public function terminar(User $usuario, Pedido $pedido): bool
    {
        return Acceso::permite($usuario, 'pedidos.terminar') && $this->verUsoMateriales($usuario, $pedido)
            && Acceso::permite($usuario, 'pedidos.modificar')
            && Acceso::permite($usuario, 'cotizaciones.validar');
    }

    public function entregar(User $usuario, Pedido $pedido): bool
    {
        return Acceso::permite($usuario, 'pedidos.entregar') && $this->view($usuario, $pedido)
            && Acceso::permite($usuario, 'pedidos.modificar');
    }

    public function create(User $usuario): bool
    {
        return $this->viewAny($usuario) && Acceso::permite($usuario, 'pedidos.crear');
    }

    public function update(User $usuario, Pedido $pedido): bool
    {
        return $this->viewAny($usuario) && Acceso::permite($usuario, 'pedidos.modificar') && in_array($pedido->estado, ['aceptado', 'en_fabricacion'], true);
    }

    public function programar(User $usuario, Pedido $pedido): bool
    {
        return $this->update($usuario, $pedido) && Acceso::permite($usuario, 'pedidos.programar');
    }

    public function editarCotizacion(User $usuario, Pedido $pedido): bool
    {
        return $this->update($usuario, $pedido) && Acceso::permite($usuario, 'cotizaciones.consultar')
            && Acceso::permite($usuario, 'cotizaciones.modificar') && Acceso::permite($usuario, 'costos.consultar');
    }

    public function aceptarCotizacion(User $usuario, Pedido $pedido): bool
    {
        return $this->update($usuario, $pedido) && Acceso::permite($usuario, 'cotizaciones.consultar')
            && Acceso::permite($usuario, 'cotizaciones.aprobar') && Acceso::permite($usuario, 'cotizaciones.validar')
            && Acceso::permite($usuario, 'costos.consultar') && Acceso::permite($usuario, 'planificacion.consultar')
            && Acceso::permite($usuario, 'planificacion.modificar');
    }
}
