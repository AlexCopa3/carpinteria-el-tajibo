<?php

namespace App\Http\Controllers;

use App\Models\TipoMueble;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TipoMuebleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TipoMueble::class);
        $datos = $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $busqueda = trim($datos['q'] ?? '');
        $consulta = TipoMueble::query();
        if ($busqueda !== '') {
            $consulta->where('nombre', 'like', "%$busqueda%");
        }
        $tipos = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.tipos_mueble.index', compact('tipos'));
    }

    public function create()
    {
        $this->authorize('create', TipoMueble::class);

        return view('admin.tipos_mueble.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', TipoMueble::class);
        if (is_string($request->nombre)) {
            $request->merge(['nombre' => trim($request->nombre)]);
        }
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('tipos_mueble', 'nombre')],
            'activo' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $datos) {
            $tipoMueble = new TipoMueble;
            // Clave técnica existente: no se solicita ni se muestra en los formularios.
            $tipoMueble->codigo = 'tipo-'.Str::lower(Str::random(20));
            $tipoMueble->nombre = $datos['nombre'];
            $tipoMueble->activo = $datos['activo'];
            $tipoMueble->save();
            $auditoria = app(AuditoriaService::class);
            $auditoria->registrar($request->user(), $tipoMueble, 'creado', [], $auditoria->datos($tipoMueble));
        });

        return redirect()->route('admin.tipos_mueble.index')->with([
            'mensaje' => 'Tipo de mueble registrado.',
            'icono' => 'success',
        ]);
    }

    public function show(TipoMueble $tipoMueble)
    {
        $this->authorize('view', $tipoMueble);

        return view('admin.tipos_mueble.show', compact('tipoMueble'));
    }

    public function edit(TipoMueble $tipoMueble)
    {
        $this->authorize('update', $tipoMueble);

        return view('admin.tipos_mueble.edit', compact('tipoMueble'));
    }

    public function update(Request $request, TipoMueble $tipoMueble)
    {
        $this->authorize('update', $tipoMueble);
        if (is_string($request->nombre)) {
            $request->merge(['nombre' => trim($request->nombre)]);
        }
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('tipos_mueble', 'nombre')->ignore($tipoMueble)],
            'activo' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $datos, $tipoMueble) {
            $tipoMueble = TipoMueble::whereKey($tipoMueble->id)->lockForUpdate()->firstOrFail();
            $auditoria = app(AuditoriaService::class);
            $antes = $auditoria->datos($tipoMueble);
            // Conservar la clave utilizada por los perfiles y los antecedentes existentes.
            $tipoMueble->nombre = $datos['nombre'];
            $tipoMueble->activo = $datos['activo'];
            $tipoMueble->save();
            $auditoria->registrar($request->user(), $tipoMueble, 'modificado', $antes, $auditoria->datos($tipoMueble));
        });

        return redirect()->route('admin.tipos_mueble.index')->with([
            'mensaje' => 'Tipo de mueble actualizado.',
            'icono' => 'success',
        ]);
    }
}
