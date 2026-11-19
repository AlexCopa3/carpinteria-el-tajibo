<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Model;

class TipoMueble extends Model
{
    use TieneAuditoria;

    protected $attributes = ['activo' => true];

    protected $table = 'tipos_mueble';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCotizacion::class, 'tipo_mueble_id');
    }
}
