<?php

namespace App\Support;

use App\Models\User;

class Acceso
{
    public static function permite(?User $user, string $permiso): bool
    {
        return $user !== null && $user->activo && $user->checkPermissionTo($permiso, 'web');
    }
}
