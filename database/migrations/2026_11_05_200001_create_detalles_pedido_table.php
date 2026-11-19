<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalles_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $table->foreignId('detalle_cotizacion_id')->unique()->constrained('detalles_cotizacion')->restrictOnDelete();
            $table->unsignedInteger('posicion');
            $table->unsignedInteger('cantidad_muebles');
            $table->decimal('precio_acordado', 18, 2);
            $table->unique(['pedido_id', 'posicion']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_pedido');
    }
};
