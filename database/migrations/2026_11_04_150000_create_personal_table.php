<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('tipo_identificacion', 20)->nullable();
            $table->string('numero_identificacion', 30)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion')->nullable();
            $table->timestamps();
            $table->unique(['tipo_identificacion', 'numero_identificacion'], 'personal_identificacion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal');
    }
};
