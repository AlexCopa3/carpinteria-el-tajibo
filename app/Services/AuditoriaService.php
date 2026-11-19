<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    public function datos(Model $registro): array
    {
        return array_diff_key($registro->only($registro->getFillable()), array_flip(['password', 'remember_token']));
    }

    public function registrar(?User $actor, Model $registro, string $accion, array $anteriores, array $nuevos): void
    {
        // Las contraseñas y tokens nunca forman parte del historial.
        $secretos = array_flip(['password', 'password_confirmation', 'current_password', 'remember_token', 'token']);
        $anteriores = array_diff_key($anteriores, $secretos);
        $nuevos = array_diff_key($nuevos, $secretos);
        if ($accion === 'modificado' && $anteriores === $nuevos) {
            return;
        }
        (new Auditoria)->forceFill([
            'registro_type' => $registro->getMorphClass(),
            'registro_id' => $registro->getKey(),
            'usuario_id' => $actor?->id,
            'nombre_registro' => $registro->nombre ?? $registro->name ?? 'Registro '.$registro->getKey(),
            'accion' => $accion,
            'datos_anteriores' => $anteriores ?: null,
            'datos_nuevos' => $nuevos ?: null,
        ])->save();
    }
}
