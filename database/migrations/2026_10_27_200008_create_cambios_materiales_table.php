<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cambios_materiales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $t->json('anterior');
            $t->json('posterior');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cambios_materiales');
    }
};
