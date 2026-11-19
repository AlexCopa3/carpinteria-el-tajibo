<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\AuditoriaService;
use App\Services\EliminacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
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
        $this->authorize('viewAny', Cliente::class);
        $busqueda = $datos['q'] ?? null;
        $consulta = Cliente::query();
        if ($busqueda) {
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('nombre', 'like', "%$busqueda%")
                    ->orWhere('telefono', 'like', "%$busqueda%")
                    ->orWhere('email', 'like', "%$busqueda%");
            });
        }
        if ($request->filled('estado')) {
            $consulta->where('activo', $request->estado === 'activo');
        }
        $clientes = $consulta->latest('id')->paginate(12)->withQueryString();

        return view('admin.clientes.index', compact('clientes'));
    }

    /**
     * Mostrar el formulario para registrar.
     */
    public function create()
    {
        $this->authorize('create', Cliente::class);

        $cliente = new Cliente(['activo' => true, 'tipo' => 'persona']);

        return view('admin.clientes.create', compact('cliente'));
    }

    /**
     * Guardar el registro validado.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Cliente::class);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['persona', 'empresa'])],
            'telefono' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:3000'],
            'activo' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $datos) {
            $cliente = new Cliente;
            $cliente->nombre = $datos['nombre'];
            $cliente->tipo = $datos['tipo'];
            $cliente->telefono = $datos['telefono'];
            if (array_key_exists('email', $datos)) {
                $cliente->email = $datos['email'];
            }
            if (array_key_exists('direccion', $datos)) {
                $cliente->direccion = $datos['direccion'];
            }
            if (array_key_exists('observaciones', $datos)) {
                $cliente->observaciones = $datos['observaciones'];
            }
            $cliente->activo = $datos['activo'];
            $cliente->save();

            $auditoria = app(AuditoriaService::class);
            $auditoria->registrar($request->user(), $cliente, 'creado', [], $auditoria->datos($cliente));
        });

        return redirect()->route('admin.clientes.index')->with([
            'mensaje' => 'Cliente registrado.',
            'icono' => 'success',
        ]);
    }

    /**
     * Mostrar los datos del registro.
     */
    public function show(Cliente $cliente)
    {
        $this->authorize('view', $cliente);

        $pedidosCliente = null;
        if (auth()->user()->can('viewAny', Pedido::class)) {
            $pedidosCliente = $cliente->pedidos()
                ->latest('id')
                ->paginate(8, ['id', 'cliente_id', 'fecha_aceptacion', 'fecha_prevista_entrega', 'estado'], 'pedidos_pagina')
                ->withQueryString()
                ->fragment('pedidos-cliente');
        }

        return view('admin.clientes.show', compact('cliente', 'pedidosCliente'));
    }

    /**
     * Mostrar el formulario para editar.
     */
    public function edit(Cliente $cliente)
    {
        $this->authorize('update', $cliente);

        return view('admin.clientes.edit', compact('cliente'));
    }

    /**
     * Actualizar el registro validado.
     */
    public function update(Request $request, Cliente $cliente)
    {
        $this->authorize('update', $cliente);
        if ($request->has('activo') && $request->boolean('activo') !== $cliente->activo) {
            $this->authorize('deactivate', $cliente);
        }
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['persona', 'empresa'])],
            'telefono' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:3000'],
            'activo' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $cliente, $datos) {
            $cliente = Cliente::whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $auditoria = app(AuditoriaService::class);
            $antes = $auditoria->datos($cliente);
            $cliente->nombre = $datos['nombre'];
            $cliente->tipo = $datos['tipo'];
            $cliente->telefono = $datos['telefono'];
            if (array_key_exists('email', $datos)) {
                $cliente->email = $datos['email'];
            }
            if (array_key_exists('direccion', $datos)) {
                $cliente->direccion = $datos['direccion'];
            }
            if (array_key_exists('observaciones', $datos)) {
                $cliente->observaciones = $datos['observaciones'];
            }
            $cliente->activo = $datos['activo'];
            $cliente->save();

            $auditoria->registrar($request->user(), $cliente, 'modificado', $antes, $auditoria->datos($cliente));
        });

        return redirect()->route('admin.clientes.show', $cliente)->with([
            'mensaje' => 'Cliente actualizado; se conserva su historial.',
            'icono' => 'success',
        ]);
    }

    /**
     * Eliminar el registro cuando sus relaciones lo permiten.
     */
    public function destroy(Request $request, Cliente $cliente, EliminacionService $eliminacionService)
    {
        $eliminacionService->eliminar($request->user(), $cliente);

        return redirect()->route('admin.clientes.index')->with([
            'mensaje' => 'Registro eliminado. Se conserva la auditoría de la operación.',
            'icono' => 'success',
        ]);
    }
}
