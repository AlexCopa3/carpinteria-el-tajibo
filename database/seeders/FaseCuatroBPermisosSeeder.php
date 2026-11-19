<?php

namespace Database\Seeders;

use App\Services\AuditoriaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FaseCuatroBPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::transaction(function () {
            $rol = Role::where('name', 'propietario')->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
            $permiso = Permission::findOrCreate('pedidos.crear', 'web');
            if (! $rol->permissions()->whereKey($permiso->id)->exists()) {
                $antes = $rol->permissions()->orderBy('name')->pluck('name')->all();
                $rol->givePermissionTo($permiso);
                $despues = $rol->permissions()->orderBy('name')->pluck('name')->all();
                app(AuditoriaService::class)->registrar(null, $rol, 'permiso_agregado_4b', ['permisos' => $antes], ['permisos' => $despues]);
            }
        }, 5);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
