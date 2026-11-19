<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Services\AuditoriaService;
use App\Services\EliminacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    /**
     * Mostrar el listado de registros.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Categoria::class);
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
        ]);
        $busqueda = trim($datos['q'] ?? '');
        $consulta = Categoria::withCount('materiales');
        if ($busqueda !== '') {
            $consulta->where('nombre', 'like', "%$busqueda%");
        }
        $categorias = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.categorias.index', compact('categorias'));
    }

    /**
     * Mostrar el formulario para registrar.
     */
    public function create()
    {
        $this->authorize('create', Categoria::class);

        return view('admin.categorias.create');
    }

    /**
     * Guardar el registro validado.
     */
    public function store(Request $request)
    {
        if (is_string($request->nombre)) {
            $request->merge(['nombre' => trim($request->nombre)]);
        }
        $this->authorize('create', Categoria::class);
        $datos = $request->validate([
            'activo' => ['sometimes', 'boolean'],
            'nombre' => ['required', 'string', 'max:100', Rule::unique('categorias')],
        ]);

        DB::transaction(function () use ($request, $datos) {
            $categoria = new Categoria;
            $auditoria = app(AuditoriaService::class);
            $categoria->nombre = $datos['nombre'];
            if (array_key_exists('activo', $datos)) {
                $categoria->activo = $datos['activo'];
            }
            $categoria->save();

            $auditoria->registrar($request->user(), $categoria, 'creado', [], $auditoria->datos($categoria));
        });

        return redirect()->route('admin.categorias.index')->with([
            'mensaje' => 'Categoría registrada.',
            'icono' => 'success',
        ]);
    }

    /**
     * Mostrar los datos del registro.
     */
    public function show(Categoria $categoria)
    {
        $this->authorize('view', $categoria);
        $materiales = $categoria->materiales()->latest('id')->paginate(12);

        return view('admin.categorias.show', compact('categoria', 'materiales'));
    }

    /**
     * Mostrar el formulario para editar.
     */
    public function edit(Categoria $categoria)
    {
        $this->authorize('update', $categoria);

        return view('admin.categorias.edit', compact('categoria'));
    }

    /**
     * Actualizar el registro validado.
     */
    public function update(Request $request, Categoria $categoria)
    {
        if (is_string($request->nombre)) {
            $request->merge(['nombre' => trim($request->nombre)]);
        }
        $this->authorize('update', $categoria);
        $datos = $request->validate([
            'activo' => ['sometimes', 'boolean'],
            'nombre' => ['required', 'string', 'max:100', Rule::unique('categorias')->ignore($categoria)],
        ]);

        DB::transaction(function () use ($request, $datos, $categoria) {
            $categoria = Categoria::whereKey($categoria->id)->lockForUpdate()->firstOrFail();
            $auditoria = app(AuditoriaService::class);
            $antes = $auditoria->datos($categoria);
            $categoria->nombre = $datos['nombre'];
            if (array_key_exists('activo', $datos)) {
                $categoria->activo = $datos['activo'];
            }
            $categoria->save();

            $auditoria->registrar($request->user(), $categoria, 'modificado', $antes, $auditoria->datos($categoria));
        });

        return redirect()->route('admin.categorias.index')->with([
            'mensaje' => 'Categoría actualizada.',
            'icono' => 'success',
        ]);
    }

    /**
     * Eliminar el registro cuando sus relaciones lo permiten.
     */
    public function destroy(Request $request, Categoria $categoria, EliminacionService $eliminacionService)
    {
        $eliminacionService->eliminar($request->user(), $categoria);

        return redirect()->route('admin.categorias.index')->with([
            'mensaje' => 'Registro eliminado. Se conserva la auditoría de la operación.',
            'icono' => 'success',
        ]);
    }
}
