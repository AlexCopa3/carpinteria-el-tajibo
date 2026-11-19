<?php

namespace App\Models;

class EstadoPedido extends RegistroInmutable
{
    protected $table = 'estados_pedido';

    protected function casts(): array
    {
        return ['ocurrido_at' => 'datetime', 'version_resultante' => 'integer'];
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
