<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('materiales_validados_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('materiales_validados_at')->nullable();
            $table->foreignId('precio_confirmado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('precio_confirmado_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('materiales_validados_por_id');
            $table->dropConstrainedForeignId('precio_confirmado_por_id');
            $table->dropColumn(['materiales_validados_at', 'precio_confirmado_at']);
        });
    }
};
