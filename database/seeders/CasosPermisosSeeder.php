<?php

namespace Database\Seeders;

use App\Services\AuditoriaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CasosPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::transaction(function () {
            foreach (['propietario' => ['casos.validar_materiales', 'casos.validar_precios'], 'maestro' => ['casos.validar_materiales']] as $nombre => $nuevos) {
                $rol = Role::where('name', $nombre)->where('guard_name', 'web')->lockForUpdate()->firstOrFail();
                if (DB::table('auditorias')->where('registro_type', Role::class)->where('registro_id', $rol->id)->where('accion', 'permisos_casos_incorporados')->exists()) {
                    continue;
                }
                $antes = $rol->permissions()->orderBy('name')->pluck('name')->all();
                foreach ($nuevos as $permiso) {
                    $rol->givePermissionTo(Permission::findOrCreate($permiso, 'web'));
                }
                app(AuditoriaService::class)->registrar(null, $rol, 'permisos_casos_incorporados', ['permisos' => $antes], ['permisos' => $rol->permissions()->orderBy('name')->pluck('name')->all()]);
            }
        }, 5);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
