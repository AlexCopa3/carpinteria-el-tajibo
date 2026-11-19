<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->unique()->constrained('cotizaciones')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->json('datos_cliente_acordados');
            $table->date('fecha_aceptacion');
            $table->date('fecha_prevista_entrega')->nullable()->index();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('registrado_por_id')->constrained('users')->restrictOnDelete();
            $table->string('estado', 30)->default('aceptado')->index();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('cotizacion_aceptada_version')->default(0);
            $table->text('observaciones')->nullable();
            $table->decimal('precio_total_acordado', 18, 2);
            $table->uuid('clave_registro')->unique();
            $table->string('hash_solicitud', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
