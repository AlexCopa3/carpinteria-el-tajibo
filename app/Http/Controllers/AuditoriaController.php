<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);
        $tipos = config('auditoria.tipos');
        $datos = $request->validate([
            'tipo' => ['nullable', Rule::in(array_keys($tipos))],
            'registro' => ['nullable', 'integer', 'min:1'],
        ]);
        $consulta = Auditoria::with('usuario');
        if ($datos['tipo'] ?? null) {
            $consulta->where('registro_type', $datos['tipo']);
        }
        if ($datos['registro'] ?? null) {
            $consulta->where('registro_id', $datos['registro']);
        }
        $auditorias = $consulta->latest('id')->paginate(20)->withQueryString();

        return view('admin.auditoria.index', compact('auditorias', 'tipos'));
    }
}
