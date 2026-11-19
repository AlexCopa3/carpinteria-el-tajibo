<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_pedido_id')->constrained('detalles_pedido')->restrictOnDelete();
            $table->foreignId('material_cotizacion_id')->nullable()->unique()->constrained('materiales_cotizacion')->restrictOnDelete();
            $table->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $table->foreignId('unidad_id')->constrained('unidades_materiales')->restrictOnDelete();
            $table->string('material_nombre');
            $table->string('unidad_nombre', 60);
            $table->string('componente', 100)->default('General');
            $table->decimal('cantidad_inicial', 18, 4)->nullable();
            $table->decimal('cantidad_planificada', 18, 4);
            $table->text('observaciones')->nullable();
            $table->foreignId('actualizado_por_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_pedido');
    }
};
