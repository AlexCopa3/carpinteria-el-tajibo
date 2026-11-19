<?php

namespace App\Models;

class Auditoria extends RegistroInmutable
{
    protected $table = 'auditorias';

    protected function casts(): array
    {
        return ['datos_anteriores' => 'array', 'datos_nuevos' => 'array'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function registro()
    {
        return $this->morphTo();
    }
}
