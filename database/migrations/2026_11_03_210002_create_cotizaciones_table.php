<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('clave_registro')->unique();
            $table->char('solicitud_hash', 64);
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->string('estado', 20)->default('borrador');
            $table->unsignedInteger('version')->default(1);
            $table->text('observaciones')->nullable();
            $table->text('condiciones')->nullable();
            $table->decimal('costo_materiales', 18, 2)->nullable();
            $table->decimal('costo_mano_obra', 18, 2)->default(0);
            $table->decimal('otros_costos', 18, 2)->default(0);
            $table->decimal('costo_total', 18, 2)->nullable();
            $table->decimal('recargo_porcentaje', 9, 4)->nullable();
            $table->decimal('precio_sugerido', 18, 2)->nullable();
            $table->decimal('precio_final', 18, 2)->nullable();
            $table->foreignId('validado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('validado_at')->nullable();
            $table->foreignId('aprobado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->json('datos_cliente_aprobados')->nullable();
            $table->timestamps();
            $table->index(['estado', 'fecha']);
            $table->index(['cliente_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
