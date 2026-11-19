<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalles_pedido';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['precio_acordado' => 'decimal:2', 'cantidad_muebles' => 'integer'];
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function detalleCotizacion()
    {
        return $this->belongsTo(DetalleCotizacion::class);
    }

    public function casoHistorico()
    {
        return $this->hasOne(CasoHistorico::class, 'detalle_pedido_id');
    }

    public function materiales()
    {
        return $this->hasMany(MaterialPedido::class)->orderBy('id');
    }

    public function precioVigente(bool $bloquear = false): string
    {
        return $this->precio_acordado;
    }

    public function costosVigentes(bool $bloquear = false): array
    {
        $costos = [];
        foreach ($this->materiales()->get() as $fila) {
            $cotizado = $fila->material_cotizacion_id ? MaterialCotizacion::whereKey($fila->material_cotizacion_id)->where('detalle_cotizacion_id', $this->detalle_cotizacion_id)->where('material_id', $fila->material_id)->where('unidad_id', $fila->unidad_id)->first() : null;
            $costos[$fila->id] = $cotizado?->costo_unitario_estimado;
        }

        return ['mano_obra_total' => $this->detalleCotizacion->mano_obra_total_aplicada,
            'otros_costos_total' => $this->detalleCotizacion->otros_costos_total, 'materiales' => $costos];
    }
}
