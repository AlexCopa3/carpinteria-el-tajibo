<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
use Illuminate\Database\Eloquent\Model;

class Ajuste extends Model
{
    use TieneAuditoria;

    protected $table = 'ajustes';
    protected $fillable = [
        'nombre',
        'descripcion',
        'direccion',
        'telefono',
        'email',
        'web',
        'logo',
    ];
}
