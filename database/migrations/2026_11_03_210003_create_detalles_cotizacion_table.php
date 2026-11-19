<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalles_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->restrictOnDelete();
            $table->unsignedInteger('posicion');
            $table->foreignId('tipo_mueble_id')->constrained('tipos_mueble')->restrictOnDelete();
            $table->foreignId('material_principal_id')->constrained('materiales')->restrictOnDelete();
            $table->string('tipo_nombre', 80);
            $table->string('material_principal_nombre');
            $table->decimal('largo_cm', 14, 4);
            $table->decimal('ancho_cm', 14, 4);
            $table->decimal('alto_cm', 14, 4);
            $table->unsignedInteger('puertas')->default(0);
            $table->unsignedInteger('cajones')->default(0);
            $table->unsignedInteger('divisiones')->default(0);
            $table->string('acabado', 150);
            $table->unsignedInteger('cantidad_muebles');
            $table->text('observaciones')->nullable();
            $table->decimal('mano_obra_unitaria', 18, 2);
            $table->decimal('mano_obra_total_nominal', 18, 2);
            $table->decimal('mano_obra_total_aplicada', 18, 2);
            $table->text('motivo_ajuste_mano_obra')->nullable();
            $table->foreignId('ajustado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('ajustado_at')->nullable();
            $table->decimal('otros_costos_total', 18, 2)->default(0);
            $table->decimal('costo_materiales_total', 18, 2)->nullable();
            $table->decimal('costo_total', 18, 2)->nullable();
            $table->decimal('precio_propuesto', 18, 2)->nullable();
            $table->decimal('precio_sugerido', 18, 2)->nullable();
            $table->decimal('precio_final', 18, 2)->nullable();
            $table->timestamps();
            $table->unique(['cotizacion_id', 'posicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_cotizacion');
    }
};
