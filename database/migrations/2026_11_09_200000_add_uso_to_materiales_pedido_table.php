<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiales_pedido', function (Blueprint $table) {
            $table->decimal('cantidad_utilizada', 18, 4)->nullable();
            $table->text('observaciones_uso')->nullable();
            $table->foreignId('uso_registrado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('uso_registrado_at')->nullable();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE materiales_pedido ADD CONSTRAINT cantidad_utilizada_no_negativa CHECK (cantidad_utilizada IS NULL OR cantidad_utilizada >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE materiales_pedido DROP CHECK cantidad_utilizada_no_negativa');
        }
        Schema::table('materiales_pedido', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uso_registrado_por_id');
            $table->dropColumn(['cantidad_utilizada', 'observaciones_uso', 'uso_registrado_at']);
        });
    }
};
