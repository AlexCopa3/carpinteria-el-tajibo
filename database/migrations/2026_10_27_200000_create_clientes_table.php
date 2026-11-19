<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('tipo', 15);
            $t->string('telefono', 40);
            $t->string('email')->nullable();
            $t->string('direccion', 500)->nullable();
            $t->text('observaciones')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $t->index(['activo', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
