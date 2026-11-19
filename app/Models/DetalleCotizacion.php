<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleCotizacion extends Model
{
    protected $table = 'detalles_cotizacion';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'largo_cm' => 'decimal:4', 'ancho_cm' => 'decimal:4', 'alto_cm' => 'decimal:4',
            'mano_obra_unitaria' => 'decimal:2', 'mano_obra_total_nominal' => 'decimal:2',
            'mano_obra_total_aplicada' => 'decimal:2', 'otros_costos_total' => 'decimal:2',
            'costo_materiales_total' => 'decimal:2', 'costo_total' => 'decimal:2',
            'precio_propuesto' => 'decimal:2', 'precio_sugerido' => 'decimal:2', 'precio_final' => 'decimal:2',
            'ajustado_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $proteger = function (DetalleCotizacion $detalle) {
            if ($detalle->cotizacion()->where(fn ($consulta) => $consulta->whereNotNull('aprobado_at')->orWhere('estado', 'rechazada'))->exists()) {
                throw new \LogicException('El detalle aprobado no se modifica ni elimina.');
            }
        };
        static::creating($proteger);
        static::updating($proteger);
        static::deleting($proteger);
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function materiales()
    {
        return $this->hasMany(MaterialCotizacion::class, 'detalle_cotizacion_id')->orderBy('posicion');
    }

    public function detallePedido()
    {
        return $this->hasOne(DetallePedido::class, 'detalle_cotizacion_id');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoMueble::class, 'tipo_mueble_id');
    }

    public function materialPrincipal()
    {
        return $this->belongsTo(Material::class, 'material_principal_id');
    }
}
