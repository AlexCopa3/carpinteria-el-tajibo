<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use TieneAuditoria;

    protected $table = 'cotizaciones';

    protected $guarded = ['*'];

    protected $fillable = [
        'cliente_id', 'responsable_id', 'fecha', 'estado', 'version', 'observaciones', 'condiciones',
        'costo_materiales', 'costo_mano_obra', 'otros_costos', 'costo_total', 'recargo_porcentaje',
        'precio_sugerido', 'precio_final', 'validado_por_id', 'validado_at', 'aprobado_por_id',
        'aprobado_at', 'datos_cliente_aprobados',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date', 'version' => 'integer', 'aprobado_at' => 'datetime', 'validado_at' => 'datetime',
            'datos_cliente_aprobados' => 'array', 'costo_materiales' => 'decimal:2',
            'costo_mano_obra' => 'decimal:2', 'otros_costos' => 'decimal:2', 'costo_total' => 'decimal:2',
            'recargo_porcentaje' => 'decimal:4', 'precio_sugerido' => 'decimal:2', 'precio_final' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Cotizacion $cotizacion) {
            if ($cotizacion->getRawOriginal('estado') === 'rechazada') {
                throw new \LogicException('La cotización rechazada se conserva sin modificaciones.');
            }
            $rechazo = $cotizacion->getRawOriginal('estado') === 'aprobada' && $cotizacion->estado === 'rechazada'
                && ! array_diff(array_keys($cotizacion->getDirty()), ['estado', 'version', 'updated_at'])
                && ! $cotizacion->pedido()->exists();
            if ($cotizacion->getRawOriginal('aprobado_at') !== null && ! $rechazo) {
                throw new \LogicException('Una cotización aprobada no se modifica.');
            }
        });
        static::deleting(function (Cotizacion $cotizacion) {
            if ($cotizacion->aprobado_at !== null || $cotizacion->estado === 'rechazada' || $cotizacion->pedido()->exists()) {
                throw new \LogicException('Una cotización aprobada no se elimina.');
            }
        });
    }

    public function getEstadoNombreAttribute(): string
    {
        return ['borrador' => 'Borrador', 'pendiente_costos' => 'Costos pendientes', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'][$this->estado] ?? $this->estado;
    }

    public function pedido()
    {
        return $this->hasOne(Pedido::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCotizacion::class, 'cotizacion_id')->orderBy('posicion');
    }
}
