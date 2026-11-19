<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use TieneAuditoria;

    protected $table = 'pedidos';

    protected $guarded = ['*'];

    protected $fillable = ['cotizacion_id', 'cliente_id', 'datos_cliente_acordados', 'fecha_aceptacion', 'fecha_prevista_entrega', 'responsable_id', 'registrado_por_id', 'estado', 'version', 'observaciones', 'precio_total_acordado'];

    protected function casts(): array
    {
        return ['datos_cliente_acordados' => 'array', 'fecha_aceptacion' => 'date', 'fecha_prevista_entrega' => 'date', 'version' => 'integer', 'cotizacion_aceptada_version' => 'integer', 'precio_total_acordado' => 'decimal:2', 'materiales_validados_at' => 'datetime', 'precio_confirmado_at' => 'datetime'];
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class)->orderBy('posicion');
    }

    public function estados()
    {
        return $this->hasMany(EstadoPedido::class)->orderBy('version_resultante');
    }

    public function materialesValidadosPor()
    {
        return $this->belongsTo(User::class, 'materiales_validados_por_id');
    }

    public function precioConfirmadoPor()
    {
        return $this->belongsTo(User::class, 'precio_confirmado_por_id');
    }

    public function getEstadoNombreAttribute(): string
    {
        return ['aceptado' => 'Aceptado', 'en_fabricacion' => 'En fabricación', 'terminado' => 'Terminado', 'entregado' => 'Entregado'][$this->estado] ?? $this->estado;
    }

    public function precioVigente(bool $bloquear = false): string
    {
        return $this->precio_total_acordado;
    }

    public function cotizacionVigente()
    {
        return $this->cotizacion;
    }
}
