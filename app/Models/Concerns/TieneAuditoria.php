<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;

trait TieneAuditoria
{
    public function auditorias()
    {
        return $this->morphMany(Auditoria::class, 'registro');
    }
}
