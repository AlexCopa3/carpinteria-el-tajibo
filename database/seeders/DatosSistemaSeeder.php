<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\TipoMueble;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatosSistemaSeeder extends Seeder
{
    // Se conservan estas claves para reconocer los datos ya cargados.
    public const PRIMERA_CARGA = 'datos_bolivianos_20261105_v1';

    public const SEGUNDA_CARGA = 'datos_bolivianos_20261105_v2';

    public array $resumen = [];

    public function run(): void
    {
        $this->cargar(self::PRIMERA_CARGA);
        $this->cargar(self::SEGUNDA_CARGA);
    }

    public function cargar(string $lote): void
    {
        $this->comprobarDestino();
        if (! in_array($lote, [self::PRIMERA_CARGA, self::SEGUNDA_CARGA], true)) {
            throw new \RuntimeException('Carga no reconocida.');
        }
        fake()->seed($lote === self::PRIMERA_CARGA ? 20261105 : 20261106);
        DB::transaction(function () use ($lote) {
            $propietario = User::role('propietario')->where('activo', true)->orderBy('id')->lockForUpdate()->firstOrFail();
            $anterior = Auditoria::where('accion', 'datos_ejemplo_cargados')->where('datos_nuevos->lote', $lote)->lockForUpdate()->first();
            if ($anterior) {
                $this->resumen[$lote] = [];
                foreach ($anterior->datos_nuevos['ids'] as $tabla => $ids) {
                    if (! in_array($tabla, ['categorias', 'clientes', 'materiales', 'existencias_materiales', 'movimientos_materiales', 'cotizaciones', 'detalles_cotizacion', 'materiales_cotizacion', 'pedidos', 'detalles_pedido', 'materiales_pedido', 'estados_pedido'], true)) {
                        throw new \RuntimeException('Tabla de carga no reconocida.');
                    }
                    $this->resumen[$lote][$tabla] = DB::table($tabla)->whereIn('id', $ids)->count();
                }
                $this->command?->info('La carga ya existe. Sus registros no se modificaron.');

                return;
            }
            if ($lote === self::SEGUNDA_CARGA && ! Auditoria::where('accion', 'datos_ejemplo_cargados')->where('datos_nuevos->lote', self::PRIMERA_CARGA)->exists()) {
                throw new \RuntimeException('Primero carga la primera parte de DatosSistemaSeeder.');
            }
            $tablas = ['categorias', 'clientes', 'materiales', 'existencias_materiales', 'movimientos_materiales', 'cotizaciones', 'detalles_cotizacion', 'materiales_cotizacion', 'pedidos', 'detalles_pedido', 'materiales_pedido', 'estados_pedido'];
            $antes = [];
            foreach ($tablas as $tabla) {
                $antes[$tabla] = DB::table($tabla)->pluck('id')->all();
            }
            $carga = ['lote' => $lote, 'propietario' => $propietario];
            // Mismo orden que las relaciones de las migraciones.
            (new CategoriasSeeder)->run($carga);
            (new ClientesSeeder)->run($carga);
            (new MaterialesSeeder)->run($carga);
            (new ExistenciasMaterialesSeeder)->run($carga);
            (new CotizacionesSeeder)->run($carga);
            (new PedidosSeeder)->run($carga);
            $nuevos = [];
            foreach ($tablas as $tabla) {
                $nuevos[$tabla] = array_values(array_diff(DB::table($tabla)->pluck('id')->all(), $antes[$tabla]));
            }
            app(AuditoriaService::class)->registrar(null, $propietario, 'datos_ejemplo_cargados', [], ['lote' => $lote, 'origen' => 'simulado', 'fuente' => 'DatosSistemaSeeder', 'ids' => $nuevos, 'alcance' => 'Ejemplos para probar los módulos, no operaciones o validaciones humanas reales.', 'precios' => 'Ilustrativos; sujetos a revisión local.']);
            $this->resumen[$lote] = array_map('count', $nuevos);
        }, 5);
    }

    public function comprobarDestino(): void
    {
        if (! config('datos_sistema.habilitado') || ! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Carga de ejemplos desactivada. Usa el comando autorizado para el proyecto local.');
        }
        if (DB::getDriverName() === 'mysql') {
            $base = DB::selectOne('SELECT DATABASE() nombre')->nombre;
            if ($base !== 'carpinteria_el_tajibo' && ! preg_match('/^carpinteria_el_tajibo_ensayo_datos_[0-9]{14}_[a-f0-9]{6}$/D', $base)) {
                throw new \RuntimeException('Base no autorizada para esta carga.');
            }
        } elseif (! (app()->environment('testing') && DB::getDriverName() === 'sqlite' && DB::connection()->getDatabaseName() === ':memory:')) {
            throw new \RuntimeException('Destino de pruebas no permitido.');
        }
        foreach (['auditorias', 'pedidos', 'personal'] as $tabla) {
            if (! Schema::hasTable($tabla)) {
                throw new \RuntimeException('El esquema del proyecto no está actualizado.');
            }
        }
        foreach (['unidad', 'pieza', 'tablero', 'metro', 'litro'] as $codigo) {
            if (! UnidadMaterial::where('codigo', $codigo)->exists()) {
                throw new \RuntimeException('Falta una unidad del catálogo. No se modificará el catálogo automáticamente.');
            }
        }
        foreach (['escritorio', 'mesa', 'silla', 'armario', 'estante', 'puerta'] as $codigo) {
            if (! TipoMueble::where('codigo', $codigo)->where('activo', true)->exists()) {
                throw new \RuntimeException('Falta un tipo de mueble activo del catálogo.');
            }
        }
    }
}
