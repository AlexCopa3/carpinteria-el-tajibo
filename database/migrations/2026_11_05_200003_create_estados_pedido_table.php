<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('ocurrido_at');
            $table->text('motivo')->nullable();
            $table->unsignedInteger('version_resultante');
            $table->uuid('clave_operacion')->unique();
            $table->string('hash_solicitud', 64);
            $table->unique(['pedido_id', 'version_resultante']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_pedido');
    }
};
