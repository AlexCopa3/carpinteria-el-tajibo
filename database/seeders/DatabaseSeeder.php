<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PermisosSeeder::class, UnidadesSeeder::class]);
        if (Schema::hasTable('tipos_mueble')) {
            $this->call(TiposMuebleSeeder::class);
        }

        if (Schema::hasTable('casos_historicos')) {
            $this->call(CasosPermisosSeeder::class);
        }

        // Una instalación local incluye los ejemplos; producción conserva solo catálogos.
        if (! config('datos_sistema.habilitado') || ! app()->environment(['local', 'testing'])) {
            return;
        }

        if (! User::role('propietario')->where('activo', true)->exists()) {
            if (User::role('propietario')->exists()) {
                throw new \RuntimeException('No hay un propietario activo. Reactiva la cuenta antes de cargar los ejemplos.');
            }
            if (! $this->command || $this->command->call('tajibo:crear-propietario') !== Command::SUCCESS) {
                throw new \RuntimeException('No se creó el propietario. Vuelve a ejecutar el comando de forma interactiva y completa sus datos.');
            }
        }

        $this->call(DatosSistemaSeeder::class);
        $this->call(CasosHistoricosSeeder::class);
        $this->call([UsuariosSeeder::class, FabricacionSeeder::class, PagosSeeder::class, AjustesSeeder::class]);
        $this->call(HistorialCarpinteriaSeeder::class);
        app(CasosHistoricosSeeder::class)->ampliar();
        app(CasosHistoricosSeeder::class)->ajustarCantidad();
        app(HistorialCarpinteriaSeeder::class)->redistribuirFechas();
        app(CasosHistoricosSeeder::class)->entregarHistorial();
        app(CasosHistoricosSeeder::class)->limitarA87();
        $this->call(ConsumosMaterialesSeeder::class);
        app(HistorialCarpinteriaSeeder::class)->finalizarIds();
    }
}
