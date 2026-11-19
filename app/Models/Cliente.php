<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    use TieneAuditoria;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'tipo',
        'telefono',
        'email',
        'direccion',
        'observaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'cliente_id');
    }
}
