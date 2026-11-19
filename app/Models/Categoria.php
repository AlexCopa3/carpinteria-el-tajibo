<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;
    use TieneAuditoria;

    protected $attributes = ['activo' => true];

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function materiales()
    {
        return $this->hasMany(Material::class, 'categoria_id');
    }
}
