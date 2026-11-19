<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_caso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_historico_id')->constrained('casos_historicos')->restrictOnDelete();
            $table->unsignedInteger('posicion');
            $table->unique(['caso_historico_id', 'posicion']);
            $table->foreignId('material_pedido_id')->nullable()->constrained('materiales_pedido')->restrictOnDelete();
            $table->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $table->foreignId('unidad_id')->constrained('unidades_materiales')->restrictOnDelete();
            $table->string('material_nombre');
            $table->string('unidad_nombre', 60);
            $table->string('componente', 100)->default('General');
            $table->decimal('cantidad_total', 18, 4);
            $table->decimal('costo_unitario_estimado', 18, 6)->nullable();
            $table->decimal('subtotal_estimado', 18, 2)->nullable();
            $table->string('fuente_costo', 40);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_caso');
    }
};
