<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_cotizacion_id')->constrained('detalles_cotizacion')->restrictOnDelete();
            $table->unsignedInteger('posicion');
            $table->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $table->foreignId('unidad_id')->constrained('unidades_materiales')->restrictOnDelete();
            $table->string('material_nombre');
            $table->string('unidad_nombre', 60);
            $table->string('componente', 100);
            $table->decimal('cantidad_unitaria', 18, 4);
            $table->decimal('cantidad_total_nominal', 18, 4);
            $table->decimal('cantidad_total_aplicada', 18, 4);
            $table->decimal('precio_referencia_capturado', 18, 6);
            $table->decimal('costo_unitario_estimado', 18, 6)->nullable();
            $table->string('estado_costo', 20);
            $table->string('fuente_costo', 30);
            $table->text('motivo_gratuidad')->nullable();
            $table->text('motivo_ajuste_cantidad')->nullable();
            $table->foreignId('ajustado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('ajustado_at')->nullable();
            $table->decimal('subtotal_nominal', 18, 2)->nullable();
            $table->decimal('subtotal_aplicado', 18, 2)->nullable();
            $table->timestamps();
            $table->unique(['detalle_cotizacion_id', 'posicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_cotizacion');
    }
};
