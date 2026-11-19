<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casos_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_pedido_id')->nullable()->unique()->constrained('detalles_pedido')->restrictOnDelete();
            $table->foreignId('tipo_mueble_id')->constrained('tipos_mueble')->restrictOnDelete();
            $table->foreignId('material_principal_id')->constrained('materiales')->restrictOnDelete();
            $table->string('tipo_nombre', 80);
            $table->string('material_principal_nombre');
            $table->date('fecha_referencia');
            $table->decimal('largo_cm', 14, 4);
            $table->decimal('ancho_cm', 14, 4);
            $table->decimal('alto_cm', 14, 4);
            $table->unsignedInteger('puertas');
            $table->unsignedInteger('cajones');
            $table->unsignedInteger('divisiones');
            $table->string('acabado', 150);
            $table->unsignedInteger('cantidad_muebles');
            $table->text('descripcion')->nullable();
            $table->string('contexto')->nullable();
            $table->string('origen', 20)->index();
            $table->string('fuente', 30);
            $table->string('fuente_caracteristicas', 40);
            $table->string('fuente_costos', 40);
            $table->string('estado_revision', 20)->default('pendiente')->index();
            $table->text('motivo_no_elegible')->nullable();
            $table->decimal('costo_materiales_estimado_total', 18, 2)->nullable();
            $table->decimal('mano_obra_estimada_total', 18, 2);
            $table->decimal('otros_costos_estimados_total', 18, 2);
            $table->decimal('costo_estimado_total', 18, 2)->nullable();
            $table->decimal('costo_presupuestado_total', 18, 2)->nullable();
            $table->decimal('precio_historico_total', 18, 2);
            $table->string('lote', 80)->nullable();
            $table->unsignedSmallInteger('posicion_lote')->nullable();
            $table->unique(['lote', 'posicion_lote']);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('materiales_revisados_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('materiales_revisados_at')->nullable();
            $table->foreignId('precio_revisado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('precio_revisado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casos_historicos');
    }
};
