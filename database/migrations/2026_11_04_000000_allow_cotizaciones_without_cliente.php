<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('cotizaciones')->whereNull('cliente_id')->exists()) {
            throw new RuntimeException('No se puede exigir cliente sin asignarlo antes a las cotizaciones existentes.');
        }
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
        });
    }
};
