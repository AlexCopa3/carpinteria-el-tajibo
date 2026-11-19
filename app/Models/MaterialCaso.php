<?php

namespace App\Models;

class MaterialCaso extends RegistroInmutable
{
    protected $table = 'materiales_caso';

    protected function casts(): array
    {
        return ['cantidad_total' => 'decimal:4', 'costo_unitario_estimado' => 'decimal:6', 'subtotal_estimado' => 'decimal:2'];
    }

    public function casoHistorico()
    {
        return $this->belongsTo(CasoHistorico::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadMaterial::class);
    }

    public function materialPedido()
    {
        return $this->belongsTo(MaterialPedido::class);
    }
}
