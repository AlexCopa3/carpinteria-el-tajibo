<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsolidacionMigracionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_instalacion_nueva_crea_directamente_el_esquema_final(): void
    {
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertTrue(Schema::hasTable('categorias'));
        $this->assertTrue(Schema::hasColumn('materiales', 'categoria_id'));
        $this->assertFalse(Schema::hasColumn('materiales', 'codigo'));
        $this->assertFalse(Schema::hasColumn('movimientos_materiales', 'material_codigo'));
        $this->assertSame(27, DB::table('migrations')->count());
        $this->assertTrue(Schema::hasColumns('materiales_pedido', ['cantidad_utilizada', 'observaciones_uso', 'uso_registrado_por_id', 'uso_registrado_at']));
        $this->assertTrue(collect(Schema::getForeignKeys('materiales'))->contains(fn ($f) => $f['columns'] === ['categoria_id'] && $f['foreign_table'] === 'categorias'));
        foreach (['presentaciones_compra', 'entradas_materiales', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'operaciones_inventario', 'movimientos_inventario', 'configuraciones_cotizacion'] as $tablaRetirada) {
            $this->assertFalse(Schema::hasTable($tablaRetirada));
        }
        $archivos = glob(database_path('migrations/*.php'));
        $this->assertCount(27, $archivos);
        foreach ($archivos as $archivo) {
            $this->assertStringNotContainsString('2026_10_27_23000', basename($archivo));
        }
    }

    public function test_reversion_respeta_el_orden_de_dependencias_en_base_aislada(): void
    {
        (require database_path('migrations/2026_11_10_200000_create_pagos_table.php'))->down();
        (require database_path('migrations/2026_11_09_220001_create_materiales_caso_table.php'))->down();
        (require database_path('migrations/2026_11_09_220000_create_casos_historicos_table.php'))->down();
        (require database_path('migrations/2026_11_09_210000_add_validaciones_to_pedidos_table.php'))->down();
        (require database_path('migrations/2026_11_09_200000_add_uso_to_materiales_pedido_table.php'))->down();
        foreach (array_reverse(glob(database_path('migrations/2026_11_05_20000*.php'))) as $archivoPedido) {
            (require $archivoPedido)->down();
        }
        (require database_path('migrations/2026_11_04_150000_create_personal_table.php'))->down();
        (require database_path('migrations/2026_11_04_000000_allow_cotizaciones_without_cliente.php'))->down();
        foreach (array_reverse(glob(database_path('migrations/2026_11_03_21000*.php'))) as $archivoNuevo) {
            (require $archivoNuevo)->down();
        }
        $archivos = glob(database_path('migrations/2026_10_27_*.php'));
        $archivos = array_filter($archivos, fn ($p) => ! str_contains($p, 'create_permission_tables'));
        foreach (array_reverse($archivos) as $archivo) {
            (require $archivo)->down();
        }
        $this->assertFalse(Schema::hasTable('pagos'));
        $this->assertFalse(Schema::hasTable('materiales'));
        $this->assertFalse(Schema::hasTable('categorias'));
        $this->assertTrue(Schema::hasTable('users'));
    }
}
