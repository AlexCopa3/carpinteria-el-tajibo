<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Rules\LogoSeguro;
use App\Services\AuditoriaService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AjusteController extends Controller
{
    public function index()
    {
        $this->authorize('gestionar', Ajuste::class);
        $ajuste = Ajuste::first();

        return view('admin.ajustes.index', compact('ajuste'));
    }

    public function store(Request $request)
    {
        $this->authorize('gestionar', Ajuste::class);

        $request->validate([
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'direccion'   => 'required|string|max:255',
            'telefono'    => 'required|string|max:50',
            'email'       => 'required|email|max:255',
            'web'         => 'nullable|string|max:255',
            'logo'        => ['nullable', 'image:allow_svg', 'mimes:jpg,jpeg,png,svg', 'max:2048', new LogoSeguro()],
        ]);

        $logoNuevo = null;
        $logoAnterior = null;

        try {
            DB::transaction(function () use ($request, &$logoNuevo, &$logoAnterior) {
                $ajuste = Ajuste::lockForUpdate()->first() ?: new Ajuste();
                $existia = $ajuste->exists;
                $auditoria = app(AuditoriaService::class);
                $antes = $existia ? $auditoria->datos($ajuste) : [];
                $logoAnterior = $ajuste->logo;

                $ajuste->nombre      = $request->nombre;
                $ajuste->descripcion = $request->descripcion;
                $ajuste->direccion   = $request->direccion;
                $ajuste->telefono    = $request->telefono;
                $ajuste->email       = $request->email;
                $ajuste->web         = $request->web;

                if ($request->hasFile('logo')) {
                    if ($logoNuevo === null) {
                        $logoNuevo = $request->file('logo')->store('logos', 'public');
                    }
                    if (! $logoNuevo) {
                        throw ValidationException::withMessages(['logo' => 'No se pudo guardar el logo. Inténtalo nuevamente.']);
                    }
                    $ajuste->logo = $logoNuevo;
                }

                $ajuste->save();

                $auditoria->registrar($request->user(), $ajuste, $existia ? 'modificado' : 'creado', $antes, $auditoria->datos($ajuste));
            }, 5);
        } catch (\Throwable $error) {
            if ($logoNuevo) {
                Storage::disk('public')->delete($logoNuevo);
            }
            if ($error instanceof QueryException && (string) $error->getCode() === '23000') {
                throw ValidationException::withMessages(['nombre' => 'Otra solicitud guardó los ajustes. Recarga la pantalla antes de volver a guardar.']);
            }
            throw $error;
        }

        if ($logoNuevo && $logoAnterior && $logoAnterior !== $logoNuevo && str_starts_with($logoAnterior, 'logos/')) {
            if (! Storage::disk('public')->delete($logoAnterior)) {
                Log::warning('No se pudo retirar el logo anterior después de guardar los ajustes.');
            }
        }

        return redirect()->route('admin.ajustes.index')->with('swal', [
            'icon' => 'success',
            'title' => '¡Guardado!',
            'text' => 'Ajustes guardados correctamente.',
        ]);
    }

    public function logo()
    {
        $ajuste = Ajuste::first();
        abort_unless($ajuste && $ajuste->logo && str_starts_with($ajuste->logo, 'logos/') && Storage::disk('public')->exists($ajuste->logo), 404);

        return Storage::disk('public')->response($ajuste->logo, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, no-cache',
        ]);
    }
}
