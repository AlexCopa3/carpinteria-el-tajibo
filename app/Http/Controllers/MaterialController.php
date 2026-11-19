<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Material;
use App\Models\UnidadMaterial;
use App\Services\CatalogoService;
use App\Services\EliminacionService;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Mostrar el listado de registros.
     */
    public function index(Request $request)
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'estado' => ['nullable', 'in:activo,inactivo'],
        ]);
        $this->authorize('viewAny', Material::class);
        $busqueda = $datos['q'] ?? null;
        $consulta = Material::with(['unidad', 'existencia', 'categoriaMaterial']);
        if ($busqueda) {
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('nombre', 'like', "%$busqueda%")
                    ->orWhereHas('categoriaMaterial', function ($categoria) use ($busqueda) {
                        $categoria->where('nombre', 'like', "%$busqueda%");
                    });
            });
        }
        if ($request->filled('estado')) {
            $consulta->where('activo', $request->estado === 'activo');
        }
        $materiales = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.materiales.index', compact('materiales'));
    }

    /**
     * Mostrar el formulario para registrar.
     */
    public function create()
    {
        $this->authorize('create', Material::class);

        $material = new Material(['activo' => true]);
        $unidades = UnidadMaterial::all();
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();

        return view('admin.materiales.create', compact('material', 'unidades', 'categorias'));
    }

    /**
     * Guardar el registro validado.
     */
    public function store(Request $request, CatalogoService $catalogoService)
    {
        $this->authorize('create', Material::class);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'especificaciones' => ['nullable', 'string', 'max:3000'],
            'unidad_id' => ['required', 'integer', 'exists:unidades_materiales,id'],
            'precio_referencia' => ['required'],
            'stock_minimo' => ['prohibited'],
            'activo' => ['required', 'boolean'],
            'cantidad' => ['prohibited'],
            'existencia' => ['prohibited'],
        ]);

        $material = $catalogoService->guardar($request->user(), $datos);

        return redirect()->route('admin.materiales.show', $material)->with([
            'mensaje' => 'Material registrado correctamente.',
            'icono' => 'success',
        ]);
    }

    /**
     * Mostrar los datos del registro.
     */
    public function show(Material $material)
    {
        $this->authorize('view', $material);
        $material->load(['unidad', 'existencia', 'cambios', 'categoriaMaterial']);

        return view('admin.materiales.show', compact('material'));
    }

    /**
     * Mostrar el formulario para editar.
     */
    public function edit(Material $material)
    {
        $this->authorize('update', $material);

        $unidades = UnidadMaterial::all();
        $categorias = Categoria::where(function ($consulta) use ($material) {
            $consulta->where('activo', true)->orWhere('id', $material->categoria_id);
        })
            ->orderBy('nombre')->get();

        return view('admin.materiales.edit', compact('material', 'unidades', 'categorias'));
    }

    /**
     * Actualizar el registro validado.
     */
    public function update(Request $request, Material $material, CatalogoService $catalogoService)
    {
        $this->authorize('update', $material);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'especificaciones' => ['nullable', 'string', 'max:3000'],
            'unidad_id' => ['required', 'integer', 'exists:unidades_materiales,id'],
            'precio_referencia' => ['required'],
            'stock_minimo' => ['prohibited'],
            'activo' => ['required', 'boolean'],
            'cantidad' => ['prohibited'],
            'existencia' => ['prohibited'],
        ]);

        $catalogoService->guardar($request->user(), $datos, $material);

        return redirect()->route('admin.materiales.show', $material)->with([
            'mensaje' => 'Material actualizado correctamente.',
            'icono' => 'success',
        ]);
    }

    /**
     * Eliminar el registro cuando sus relaciones lo permiten.
     */
    public function destroy(Request $request, Material $material, EliminacionService $eliminacionService)
    {
        $eliminacionService->eliminar($request->user(), $material);

        return redirect()->route('admin.materiales.index')->with([
            'mensaje' => 'Registro eliminado. Se conserva la auditoría de la operación.',
            'icono' => 'success',
        ]);
    }
}
