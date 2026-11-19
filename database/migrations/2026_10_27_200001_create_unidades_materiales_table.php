<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_materiales', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 20)->unique();
            $t->string('nombre', 60);
            $t->boolean('fraccionaria');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_materiales');
    }
};
