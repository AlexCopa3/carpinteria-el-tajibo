<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class RegistroInmutable extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('El historial confirmado no se modifica. Registra una operación compensatoria.'));
        static::deleting(fn () => throw new \LogicException('El historial confirmado no se elimina.'));
    }
}
