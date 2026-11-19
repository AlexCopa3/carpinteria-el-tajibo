<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Material;
use App\Models\Pedido;
use App\Support\Acceso;

class DashboardController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();
        $indicadores = [];
        $cotizacionesRecientes = collect();
        $tableroPedidos = [];

        if (Acceso::permite($usuario, 'clientes.consultar')) {
            $indicadores['clientes'] = Cliente::count();
        }
        if (Acceso::permite($usuario, 'materiales.consultar')) {
            $indicadores['materiales'] = Material::where('activo', true)->count();
        }
        if (Acceso::permite($usuario, 'cotizaciones.consultar')) {
            $indicadores['cotizaciones'] = Cotizacion::whereBetween('fecha', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])->count();
            $indicadores['cotizaciones_pendientes'] = Cotizacion::whereIn('estado', ['borrador', 'pendiente_costos'])->count();
            $cotizacionesRecientes = Cotizacion::select('id', 'fecha', 'estado')->orderByDesc('fecha')->orderByDesc('id')->limit(5)->get();
        }
        if (Acceso::permite($usuario, 'pedidos.consultar')) {
            $totalesPedidos = Pedido::selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
            $indicadores['pedidos_aceptados'] = (int) ($totalesPedidos['aceptado'] ?? 0);
            $indicadores['pedidos_fabricacion'] = (int) ($totalesPedidos['en_fabricacion'] ?? 0);

            foreach (['aceptado', 'en_fabricacion', 'terminado', 'entregado'] as $estado) {
                $consulta = Pedido::select('id', 'estado', 'fecha_prevista_entrega')->where('estado', $estado);

                if (in_array($estado, ['aceptado', 'en_fabricacion'], true)) {
                    $consulta->orderByRaw('fecha_prevista_entrega IS NULL')
                        ->orderBy('fecha_prevista_entrega')->orderBy('id');
                } else {
                    $consulta->orderByDesc('updated_at')->orderByDesc('id');
                }

                $tableroPedidos[$estado] = [
                    'total' => (int) ($totalesPedidos[$estado] ?? 0),
                    'pedidos' => $consulta->limit(5)->get(),
                ];
            }
        }

        return view('admin.index', compact('indicadores', 'cotizacionesRecientes', 'tableroPedidos'));
    }
}
