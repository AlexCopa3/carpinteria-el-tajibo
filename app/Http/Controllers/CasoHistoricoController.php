<?php

namespace App\Http\Controllers;

use App\Http\Requests\Casos\RevisarCasoRequest;
use App\Models\CasoHistorico;
use App\Models\TipoMueble;
use App\Services\RevisionCasoService;
use Illuminate\Http\Request;

class CasoHistoricoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CasoHistorico::class);
        $datos = $request->validate(['q' => 'nullable|string|max:200', 'tipo' => 'nullable|integer|exists:tipos_mueble,id', 'origen' => 'nullable|in:real,simulado', 'revision' => 'nullable|in:pendiente,revisado,descartado']);
        $consulta = CasoHistorico::query();
        if (! empty($datos['q'])) {
            $consulta->where(function ($consulta) use ($datos) {
                $consulta->where('tipo_nombre', 'like', '%'.$datos['q'].'%')->orWhere('material_principal_nombre', 'like', '%'.$datos['q'].'%');
                if (preg_match('/^\d+$/D', $datos['q'])) {
                    $consulta->orWhere('id', $datos['q']);
                }
            });
        }
        if (! empty($datos['tipo'])) {
            $consulta->where('tipo_mueble_id', $datos['tipo']);
        }
        if (! empty($datos['origen'])) {
            $consulta->where('origen', $datos['origen']);
        }
        if (! empty($datos['revision'])) {
            $consulta->where('estado_revision', $datos['revision']);
        }
        $casos = $consulta->latest('id')->paginate(12)->withQueryString();
        $totalCasos = CasoHistorico::count();
        $tipos = TipoMueble::orderBy('nombre')->get();

        return view('admin.casos.index', compact('casos', 'tipos', 'totalCasos'));
    }

    public function show(CasoHistorico $caso)
    {
        $this->authorize('view', $caso);
        $caso->load('materiales', 'materialesRevisadosPor', 'precioRevisadoPor', 'detallePedido.pedido');

        return view('admin.casos.show', compact('caso'));
    }

    public function revisar(RevisarCasoRequest $request, CasoHistorico $caso, RevisionCasoService $service)
    {
        $caso = $service->revisar($request->user(), $caso, $request->validated());

        return redirect()->route('admin.casos.show', $caso)->with(['mensaje' => 'Revisión registrada. El origen y los datos históricos se conservaron.', 'icono' => 'success']);
    }
}
