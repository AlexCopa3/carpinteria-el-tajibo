<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ReporteController extends Controller
{
    public function index(Request $request, ReporteService $service)
    {
        $service->autorizar($request->user());

        return view('admin.reportes.index');
    }

    public function show(Request $request, string $tipo, ReporteService $service)
    {
        $reporte = $service->generar($request->user(), $tipo, $request->query());
        $pagina = (int) ($reporte['filtros']['page'] ?? 1);
        $filas = new LengthAwarePaginator(array_slice($reporte['filas'], ($pagina - 1) * 20, 20), count($reporte['filas']), 20, $pagina,
            ['path' => $request->url(), 'query' => $request->query()]);

        return view('admin.reportes.show', compact('reporte', 'filas'));
    }

    public function pdf(Request $request, string $tipo, ReporteService $service)
    {
        $reporte = $service->generar($request->user(), $tipo, $request->query());
        if (count($reporte['filas']) > 500) {
            throw ValidationException::withMessages(['pdf' => 'El PDF admite hasta 500 registros por documento. Ajusta los filtros para exportar sin omitir registros.']);
        }
        $ajuste = Ajuste::first();
        $negocio = ['nombre' => $ajuste?->nombre ?? 'Carpintería El Tajibo', 'direccion' => $ajuste?->direccion, 'telefono' => $ajuste?->telefono];

        return Pdf::loadView('pdf.reporte', compact('reporte', 'negocio'))->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->stream('reporte-'.$tipo.'.pdf', ['Attachment' => false])->header('Cache-Control', 'private, no-store');
    }
}
