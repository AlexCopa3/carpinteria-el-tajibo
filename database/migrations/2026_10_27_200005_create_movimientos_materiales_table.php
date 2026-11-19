<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_materiales', function (Blueprint $t) {
            $t->id();
            $t->uuid('clave')->unique();
            $t->char('solicitud_hash', 64);
            $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $t->text('motivo');
            $t->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $t->foreignId('unidad_id')->constrained('unidades_materiales')->restrictOnDelete();
            $t->string('material_nombre');
            $t->string('unidad_codigo', 20);
            $t->string('tipo', 25);
            $t->decimal('cantidad', 18, 4);
            $t->decimal('existencia_anterior', 18, 4);
            $t->decimal('existencia_resultante', 18, 4);
            $t->decimal('precio_referencia_historico', 18, 6);
            $t->string('estado_costo', 20);
            $t->decimal('costo_total', 18, 2)->nullable();
            $t->decimal('costo_unitario_base', 24, 10)->nullable();
            $t->timestamps();
            $t->index(['material_id', 'id']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE movimientos_materiales ADD CONSTRAINT movimiento_material_valido CHECK (cantidad > 0 AND existencia_anterior >= 0 AND existencia_resultante >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_materiales');
    }
};
