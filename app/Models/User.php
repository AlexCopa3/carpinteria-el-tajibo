<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoria;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use TieneAuditoria;

    protected $attributes = ['activo' => true, 'password_temporal' => false];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles { hasPermissionTo as protected permisoSpatie; }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Ficha de datos personales vinculada a la cuenta. */
    public function personal()
    {
        return $this->hasOne(Personal::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'password_temporal' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function permisosIndividuales()
    {
        return $this->hasMany(PermisoUsuario::class, 'user_id');
    }

    /** La excepción individual prevalece también en Gate y en @can. */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (! $this->activo) {
            return false;
        }
        $permission = $this->filterPermission($permission, $guardName);
        // La administración de usuarios y permisos queda reservada al propietario.
        if ($permission->guard_name === 'web' && str_starts_with($permission->name, 'usuarios.')) {
            return $this->hasRole('propietario', 'web');
        }
        if ($permission->guard_name === 'web' && $this->hasRole('propietario', 'web')
            && in_array($permission->name, config('gestion_permisos.proteccion_propietario', []), true)) {
            return true;
        }
        if (! $this->relationLoaded('permisosIndividuales')) {
            // Permite desplegar el código antes de la migración aditiva controlada.
            $this->setRelation('permisosIndividuales', \Illuminate\Support\Facades\Schema::hasTable('permisos_usuario')
                ? $this->permisosIndividuales()->get() : new \Illuminate\Database\Eloquent\Collection);
        }
        $excepcion = $this->permisosIndividuales->firstWhere('permission_id', $permission->id);
        if ($excepcion !== null) {
            return $excepcion->permitido;
        }

        return $this->permisoSpatie($permission, $guardName);
    }
}
