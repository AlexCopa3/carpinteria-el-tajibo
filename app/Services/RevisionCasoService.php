<?php

namespace App\Services;

use App\Http\Requests\Casos\RevisarCasoRequest;
use App\Models\CasoHistorico;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RevisionCasoService
{
    public function revisar(User $actor, CasoHistorico $caso, array $datos): CasoHistorico
    {
        $datos = PedidoService::validar($datos, RevisarCasoRequest::reglas());

        return DB::transaction(function () use ($actor, $caso, $datos) {
            $caso = CasoHistorico::whereKey($caso->id)->lockForUpdate()->firstOrFail();
            $capacidad = match ($datos['accion']) {
                'materiales' => 'validarMateriales','precio' => 'validarPrecio','descartar' => 'descartar'
            };
            Gate::forUser($actor->fresh())->authorize($capacidad, $caso);
            if ($caso->version !== (int) $datos['version']) {
                PedidoService::error('version', 'El caso cambió. Recarga antes de revisar.');
            }
            if ($caso->estado_revision === 'descartado') {
                PedidoService::error('caso', 'El caso fue descartado; no se puede validar por esta vía.');
            }
            if ($datos['accion'] !== 'descartar' && $caso->motivo_no_elegible) {
                PedidoService::error('caso', 'El caso tiene datos pendientes o inconsistentes; no se valida rellenándolos automáticamente.');
            }
            $antes = $caso->getAttributes();
            if ($datos['accion'] === 'descartar') {
                if (mb_strlen(trim($datos['observaciones'] ?? '')) < 5) {
                    PedidoService::error('observaciones', 'Explica el motivo con al menos 5 caracteres.');
                }
                $caso->estado_revision = 'descartado';
            } elseif ($datos['accion'] === 'materiales') {
                if ($caso->materiales_revisados_at) {
                    return $caso;
                }
                $caso->materiales_revisados_por_id = $actor->id;
                $caso->materiales_revisados_at = now();
            } else {
                if ($caso->precio_revisado_at) {
                    return $caso;
                }
                $caso->precio_revisado_por_id = $actor->id;
                $caso->precio_revisado_at = now();
            }
            if ($caso->estado_revision !== 'descartado') {
                $caso->estado_revision = $caso->materiales_revisados_at && $caso->precio_revisado_at ? 'revisado' : 'pendiente';
            }
            $caso->version++;
            $caso->save();
            app(AuditoriaService::class)->registrar($actor, $caso, 'caso_revisado', $antes, ['caso' => $caso->getAttributes(), 'accion' => $datos['accion'], 'observaciones' => $datos['observaciones'] ?? null]);

            return $caso;
        }, 5);
    }
}
