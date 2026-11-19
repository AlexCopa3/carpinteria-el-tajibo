<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use TieneAuditoria;

    protected $table = 'pagos';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['pedido_id' => 'integer', 'registrado_por_id' => 'integer', 'fecha' => 'date', 'importe' => 'decimal:2', 'version' => 'integer'];
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
