<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Model;

class MaterialPedido extends Model
{
    protected $table = 'materiales_pedido';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['cantidad_inicial' => 'decimal:4', 'cantidad_planificada' => 'decimal:4', 'cantidad_utilizada' => 'decimal:4', 'uso_registrado_at' => 'datetime'];
    }

    public function usoRegistradoPor()
    {
        return $this->belongsTo(User::class, 'uso_registrado_por_id');
    }

    public function getDiferenciaUtilizadaAttribute(): ?BigDecimal
    {
        return $this->cantidad_utilizada === null ? null : BigDecimal::of($this->cantidad_utilizada)->minus($this->cantidad_planificada);
    }

    public function detalle()
    {
        return $this->belongsTo(DetallePedido::class, 'detalle_pedido_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadMaterial::class, 'unidad_id');
    }

    public function origen()
    {
        return $this->belongsTo(MaterialCotizacion::class, 'material_cotizacion_id');
    }
}
