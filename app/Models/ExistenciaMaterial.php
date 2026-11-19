<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExistenciaMaterial extends Model
{
    protected $table = 'existencias_materiales';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:4'];
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
