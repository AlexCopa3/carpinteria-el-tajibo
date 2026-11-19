<?php

namespace App\Policies;

use App\Models\CasoHistorico;
use App\Models\User;
use App\Support\Acceso;

class CasoHistoricoPolicy
{
    public function viewAny(User $usuario): bool
    {
        return Acceso::permite($usuario, 'casos.consultar');
    }

    public function view(User $usuario, CasoHistorico $caso): bool
    {
        return $this->viewAny($usuario);
    }

    public function verCostos(User $usuario, CasoHistorico $caso): bool
    {
        return $this->view($usuario, $caso) && Acceso::permite($usuario, 'costos.consultar');
    }

    public function validarMateriales(User $usuario, CasoHistorico $caso): bool
    {
        return $this->view($usuario, $caso) && Acceso::permite($usuario, 'casos.validar_materiales');
    }

    public function validarPrecio(User $usuario, CasoHistorico $caso): bool
    {
        return $this->verCostos($usuario, $caso) && Acceso::permite($usuario, 'casos.validar_precios');
    }

    public function descartar(User $usuario, CasoHistorico $caso): bool
    {
        return $this->validarPrecio($usuario, $caso);
    }
}
