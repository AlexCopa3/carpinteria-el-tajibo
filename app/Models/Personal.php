<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'personal';

    protected $fillable = [
        'user_id',
        'tipo_identificacion',
        'numero_identificacion',
        'telefono',
        'direccion',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
