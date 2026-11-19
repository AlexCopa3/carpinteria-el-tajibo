<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('existencias_materiales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->unique()->constrained('materiales')->restrictOnDelete();
            $t->decimal('cantidad', 18, 4)->default('0');
            $t->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE existencias_materiales ADD CONSTRAINT saldo_no_negativo CHECK (cantidad >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('existencias_materiales');
    }
};
