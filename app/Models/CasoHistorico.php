<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasoHistorico extends Model
{
    use HasFactory;

    protected $table = 'casos_historicos';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['fecha_referencia' => 'date', 'largo_cm' => 'decimal:4', 'ancho_cm' => 'decimal:4', 'alto_cm' => 'decimal:4', 'cantidad_muebles' => 'integer', 'puertas' => 'integer', 'cajones' => 'integer', 'divisiones' => 'integer', 'version' => 'integer', 'costo_materiales_estimado_total' => 'decimal:2', 'mano_obra_estimada_total' => 'decimal:2', 'otros_costos_estimados_total' => 'decimal:2', 'costo_estimado_total' => 'decimal:2', 'costo_presupuestado_total' => 'decimal:2', 'precio_historico_total' => 'decimal:2', 'materiales_revisados_at' => 'datetime', 'precio_revisado_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (CasoHistorico $caso) {
            $permitidos = ['estado_revision', 'materiales_revisados_por_id', 'materiales_revisados_at', 'precio_revisado_por_id', 'precio_revisado_at', 'version', 'updated_at'];
            if (array_diff(array_keys($caso->getDirty()), $permitidos)) {
                throw new \LogicException('La instantánea del caso no se reescribe. Conserva los datos de origen.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Los casos históricos se conservan; pueden descartarse mediante revisión.'));
    }

    public function materiales()
    {
        return $this->hasMany(MaterialCaso::class)->orderBy('posicion');
    }

    public function detallePedido()
    {
        return $this->belongsTo(DetallePedido::class);
    }

    public function tipoMueble()
    {
        return $this->belongsTo(TipoMueble::class);
    }

    public function materialPrincipal()
    {
        return $this->belongsTo(Material::class, 'material_principal_id');
    }

    public function materialesRevisadosPor()
    {
        return $this->belongsTo(User::class, 'materiales_revisados_por_id');
    }

    public function precioRevisadoPor()
    {
        return $this->belongsTo(User::class, 'precio_revisado_por_id');
    }
}
