<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->string('categoria', 100);
            $t->text('especificaciones')->nullable();
            $t->foreignId('unidad_id')->constrained('unidades_materiales')->restrictOnDelete();
            $t->decimal('precio_referencia', 18, 6);
            $t->decimal('stock_minimo', 18, 4)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $t->foreignId('categoria_id')->nullable()->constrained('categorias')->restrictOnDelete();
            $t->index(['activo', 'categoria']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE materiales ADD CONSTRAINT referencia_no_negativa CHECK (precio_referencia >= 0 AND (stock_minimo IS NULL OR stock_minimo >= 0))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales');
    }
};
