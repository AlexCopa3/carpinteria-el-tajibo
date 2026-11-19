<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadMaterial extends Model
{
    protected $table = 'unidades_materiales';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fraccionaria' => 'boolean'];
    }

    public function materiales()
    {
        return $this->hasMany(Material::class, 'unidad_id');
    }
}
