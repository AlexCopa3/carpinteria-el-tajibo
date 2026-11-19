<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\Pedido;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\PagoService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagosSeeder extends Seeder
{
    public function run(): void
    {
        app(DatosSistemaSeeder::class)->comprobarDestino();
        DB::transaction(function (): void {
            $propietario = User::role('propietario')->where('activo', true)->lockForUpdate()->firstOrFail();
            if (Auditoria::where('accion', 'datos_pagos_cargados')->exists()) {
                return;
            }
            $ids = Auditoria::where('accion', 'datos_ejemplo_cargados')->get()->flatMap(fn ($auditoria) => $auditoria->datos_nuevos['ids']['pedidos'] ?? [])->all();
            $pedidos = Pedido::whereIn('id', $ids)->orderBy('id')->limit(3)->get();
            if ($pedidos->count() !== 3) {
                throw new \RuntimeException('Faltan pedidos iniciales para preparar los pagos.');
            }
            foreach ($pedidos as $indice => $pedido) {
                $total = BigDecimal::of($pedido->precio_total_acordado);
                $anticipo = $total->multipliedBy('0.5')->toScale(2, RoundingMode::DOWN);
                $datos = ['pedido_id' => $pedido->id, 'fecha' => today()->toDateString(), 'observaciones' => 'Anticipo del pedido.'];
                app(PagoService::class)->guardar($propietario, $datos + ['clave_registro' => (string) Str::uuid(), 'importe' => (string) $anticipo, 'medio' => $indice === 1 ? 'qr' : 'efectivo']);
                if ($indice === 0) {
                    app(PagoService::class)->guardar($propietario, $datos + ['clave_registro' => (string) Str::uuid(), 'importe' => (string) $total->minus($anticipo)->toScale(2), 'medio' => 'qr']);
                }
            }
            app(AuditoriaService::class)->registrar(null, $propietario, 'datos_pagos_cargados', [], ['pedidos' => $pedidos->pluck('id')->all(), 'origen' => 'simulado', 'fuente' => 'PagosSeeder']);
        });
    }
}
