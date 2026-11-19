<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\ExistenciaMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExistenciaMaterialController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Material::class);
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'material_id' => ['nullable', 'integer', 'exists:materiales,id'],
        ]);
        $consulta = Material::with(['unidad', 'existencia', 'categoriaMaterial']);
        if (! empty($datos['q'])) {
            $busqueda = $datos['q'];
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('nombre', 'like', "%$busqueda%")
                    ->orWhereHas('categoriaMaterial', fn ($categoria) => $categoria->where('nombre', 'like', "%$busqueda%"));
            });
        }
        if (! empty($datos['material_id'])) {
            $consulta->whereKey($datos['material_id']);
        }
        $materiales = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.existencias.index', compact('materiales'));
    }

    public function show(Material $material)
    {
        $this->authorize('view', $material);
        $material->load(['unidad', 'existencia']);
        $movimientos = $material->movimientos()->with('usuario')
            ->latest('id')->paginate(15);

        return view('admin.existencias.show', compact('material', 'movimientos'));
    }

    public function create(Material $material)
    {
        $this->authorize('cantidades', $material);
        $material->load(['unidad', 'existencia']);
        $corregir = false;
        $clave = (string) Str::uuid();

        return view('admin.existencias.create', compact('material', 'corregir', 'clave'));
    }

    public function store(Request $request, Material $material, ExistenciaMaterialService $existenciaMaterialService)
    {
        $this->authorize('cantidades', $material);
        $datos = $request->validate([
            'clave' => 'required|uuid', 'cantidad' => 'required', 'unidad_id' => 'required|integer',
            'existencia_esperada' => 'required', 'motivo' => 'required|string|min:5|max:2000',
        ]);
        $existenciaMaterialService->guardarCantidad($request->user(), $material, $datos);

        return redirect()->route('admin.existencias.index', ['material_id' => $material->id])->with([
            'mensaje' => 'Cantidad agregada correctamente.', 'icono' => 'success',
        ]);
    }

    public function edit(Material $material)
    {
        $this->authorize('cantidades', $material);
        $material->load(['unidad', 'existencia']);
        $corregir = true;
        $clave = (string) Str::uuid();

        return view('admin.existencias.edit', compact('material', 'corregir', 'clave'));
    }

    public function update(Request $request, Material $material, ExistenciaMaterialService $existenciaMaterialService)
    {
        $this->authorize('cantidades', $material);
        $datos = $request->validate([
            'clave' => 'required|uuid', 'cantidad' => 'required', 'unidad_id' => 'required|integer',
            'existencia_esperada' => 'required', 'motivo' => 'required|string|min:5|max:2000',
        ]);
        $existenciaMaterialService->guardarCantidad($request->user(), $material, $datos, true);

        return redirect()->route('admin.existencias.index', ['material_id' => $material->id])->with([
            'mensaje' => 'Existencia corregida correctamente.', 'icono' => 'success',
        ]);
    }
}
