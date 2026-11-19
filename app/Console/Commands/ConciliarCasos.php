<?php

namespace App\Console\Commands;

use App\Models\CasoHistorico;
use App\Models\Pedido;
use App\Services\RetencionCasoService;
use Illuminate\Console\Command;

class ConciliarCasos extends Command
{
    protected $signature = 'tajibo:conciliar-casos {--aplicar : Retener los casos faltantes sin modificar pedidos}';

    protected $description = 'Consulta o concilia casos de pedidos terminados/entregados con validaciones finales.';

    public function handle(RetencionCasoService $service): int
    {
        $consulta = Pedido::whereIn('estado', ['terminado', 'entregado'])->whereNotNull('materiales_validados_at')->whereNotNull('precio_confirmado_at');
        $this->info('Pedidos candidatos: '.$consulta->count());
        if (! $this->option('aplicar')) {
            $this->info('Solo consulta. Usa --aplicar para retener casos faltantes.');

            return self::SUCCESS;
        }
        $antes = CasoHistorico::count();
        $consulta->chunkById(50, function ($pedidos) use ($service) {
            foreach ($pedidos as $pedido) {
                $service->retener($pedido);
            }
        });
        $this->info('Casos nuevos: '.(CasoHistorico::count() - $antes));

        return self::SUCCESS;
    }
}
