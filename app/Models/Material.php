<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;
    use TieneAuditoria;

    protected $table = 'materiales';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'categoria_id',
        'especificaciones',
        'unidad_id',
        'precio_referencia',
        'stock_minimo',
        'activo',
    ];

    protected function casts(): array
    {
        return ['categoria_id' => 'integer', 'unidad_id' => 'integer', 'activo' => 'boolean', 'precio_referencia' => 'decimal:6', 'stock_minimo' => 'decimal:4'];
    }

    public function scopePrincipales($consulta)
    {
        return $consulta->where('activo', true)->whereHas('categoriaMaterial', function ($categoria) {
            $categoria->where('nombre', config('tajibo.categoria_material_principal'))->where('activo', true);
        });
    }

    public function categoriaMaterial()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function unidad()
    {
        return $this->belongsTo(UnidadMaterial::class, 'unidad_id');
    }

    public function existencia()
    {
        return $this->hasOne(ExistenciaMaterial::class, 'material_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoMaterial::class, 'material_id');
    }

    public function cambios()
    {
        return $this->hasMany(CambioMaterial::class, 'material_id');
    }

    public function materialesCotizacion()
    {
        return $this->hasMany(MaterialCotizacion::class, 'material_id');
    }
}
