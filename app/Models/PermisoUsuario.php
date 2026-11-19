<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class PermisoUsuario extends Model
{
    protected $table = 'permisos_usuario';
    protected $fillable = ['user_id', 'permission_id', 'permitido'];

    protected function casts(): array
    {
        return ['permitido' => 'boolean'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function permiso()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
