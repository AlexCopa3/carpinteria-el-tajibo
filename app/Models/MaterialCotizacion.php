<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialCotizacion extends Model
{
    protected $table = 'materiales_cotizacion';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'cantidad_unitaria' => 'decimal:4', 'cantidad_total_nominal' => 'decimal:4',
            'cantidad_total_aplicada' => 'decimal:4', 'precio_referencia_capturado' => 'decimal:6',
            'costo_unitario_estimado' => 'decimal:6', 'subtotal_nominal' => 'decimal:2',
            'subtotal_aplicado' => 'decimal:2', 'ajustado_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $proteger = function (MaterialCotizacion $material) {
            if ($material->detalle()->whereHas('cotizacion', fn ($consulta) => $consulta->where(fn ($estado) => $estado->whereNotNull('aprobado_at')->orWhere('estado', 'rechazada')))->exists()) {
                throw new \LogicException('Los materiales aprobados no se modifican ni eliminan.');
            }
        };
        static::creating($proteger);
        static::updating($proteger);
        static::deleting($proteger);
    }

    public function detalle()
    {
        return $this->belongsTo(DetalleCotizacion::class, 'detalle_cotizacion_id');
    }

    public function materialesPedido()
    {
        return $this->hasMany(MaterialPedido::class, 'material_cotizacion_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadMaterial::class, 'unidad_id');
    }
}
