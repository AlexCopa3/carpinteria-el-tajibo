<?php

namespace App\Support;

use App\Models\User;

class Navigation
{
    public function forUser(User $user): array
    {
        $grupos = [];
        foreach (config('navegacion') as $seccion => $items) {
            $visibles = array_values(array_filter($items, fn (array $item) => Acceso::permite($user, $item['permiso'])));
            if ($visibles) {
                $grupos[$seccion] = $visibles;
            }
        }

        return $grupos;
    }
}
