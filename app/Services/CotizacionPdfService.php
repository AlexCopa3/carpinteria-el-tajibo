<?php

namespace App\Services;

use App\Models\Ajuste;
use App\Models\Cotizacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class CotizacionPdfService
{
    public function generar(User $actor, Cotizacion $cotizacion)
    {
        $cotizacion = Cotizacion::with('detalles')->findOrFail($cotizacion->id);
        Gate::forUser($actor->fresh())->authorize('view', $cotizacion);
        abort_unless($cotizacion->estado === 'aprobada' && $cotizacion->aprobado_at !== null && $cotizacion->precio_final !== null, 403);
        $ajuste = Ajuste::first();
        $logo = null;
        if ($ajuste?->logo && str_starts_with($ajuste->logo, 'logos/')) {
            $path = realpath(storage_path('app/public/'.$ajuste->logo));
            $raiz = realpath(storage_path('app/public/logos'));
            if ($path && $raiz && str_starts_with($path, $raiz.DIRECTORY_SEPARATOR)) {
                $mime = mime_content_type($path);
                if (in_array($mime, ['image/png', 'image/jpeg', 'image/svg+xml'], true)) {
                    $logo = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
                }
            }
        }
        $cliente = $cotizacion->datos_cliente_aprobados;
        $detalles = $cotizacion->detalles->map(fn ($detalle) => $detalle->only([
            'tipo_nombre', 'material_principal_nombre', 'largo_cm', 'ancho_cm', 'alto_cm',
            'puertas', 'cajones', 'divisiones', 'acabado', 'cantidad_muebles', 'observaciones', 'precio_final',
        ]));
        $documento = [
            'id' => $cotizacion->id,
            'fecha' => $cotizacion->fecha->format('d/m/Y'),
            'aprobacion' => $cotizacion->aprobado_at->format('d/m/Y'),
            'total' => $cotizacion->precio_final,
            'condiciones' => $cotizacion->condiciones,
            'observaciones' => $cotizacion->observaciones,
        ];
        $negocio = ['nombre' => $ajuste?->nombre ?? 'Carpintería El Tajibo', 'direccion' => $ajuste?->direccion, 'telefono' => $ajuste?->telefono, 'email' => $ajuste?->email];

        return Pdf::loadView('pdf.cotizacion', compact('documento', 'cliente', 'detalles', 'negocio', 'logo'))
            ->setPaper('a4')->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->stream('cotizacion-'.$cotizacion->id.'.pdf', ['Attachment' => false])
            ->header('Cache-Control', 'private, no-store');
    }
}
