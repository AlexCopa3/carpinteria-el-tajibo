<?php

namespace App\Models;

class CambioMaterial extends RegistroInmutable
{
    protected $table = 'cambios_materiales';

    protected function casts(): array
    {
        return ['anterior' => 'array', 'posterior' => 'array'];
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
