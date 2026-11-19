<?php

namespace App\Models;

class MovimientoMaterial extends RegistroInmutable
{
    protected $table = 'movimientos_materiales';

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:4', 'existencia_anterior' => 'decimal:4', 'existencia_resultante' => 'decimal:4', 'precio_referencia_historico' => 'decimal:6', 'costo_total' => 'decimal:2', 'costo_unitario_base' => 'decimal:10'];
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadMaterial::class, 'unidad_id');
    }
}
