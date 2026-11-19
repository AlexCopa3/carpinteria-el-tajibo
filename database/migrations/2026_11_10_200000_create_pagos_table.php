<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('importe', 18, 2);
            $table->string('medio', 20);
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->uuid('clave_registro')->unique();
            $table->char('hash_solicitud', 64);
            $table->timestamps();
            $table->index(['pedido_id', 'fecha']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pagos ADD CONSTRAINT pago_importe_positivo CHECK (importe > 0), ADD CONSTRAINT pago_version_positiva CHECK (version >= 1), ADD CONSTRAINT pago_medio_valido CHECK (medio IN ('efectivo','qr'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
