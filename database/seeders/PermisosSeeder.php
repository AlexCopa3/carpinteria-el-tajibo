<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            foreach (config('permisos') as $nombre => $permisos) {
                foreach ($permisos as $permiso) {
                    Permission::findOrCreate($permiso, 'web');
                }

                $rol = Role::findOrCreate($nombre, 'web');
                $personalizado = Schema::hasTable('auditorias')
                    && DB::table('auditorias')->where('registro_type', Role::class)->where('registro_id', $rol->id)->where('accion', 'permisos_actualizados')->exists();
                if (! $personalizado) {
                    $rol->syncPermissions($permisos);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
