<?php

namespace App\Services;

use App\Models\CambioMaterial;
use App\Models\Categoria;
use App\Models\Material;
use App\Models\UnidadMaterial;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CatalogoService
{
    public function guardar(User $actor, array $datos, ?Material $material = null): Material
    {
        Gate::forUser($actor)->authorize($material ? 'update' : 'create', $material ?? Material::class);

        return DB::transaction(function () use ($actor, $datos, $material) {
            $material = $material ? Material::whereKey($material->id)->lockForUpdate()->firstOrFail() : new Material;
            $unidad = UnidadMaterial::findOrFail($datos['unidad_id']);
            $categoria = Categoria::whereKey($datos['categoria_id'] ?? null)->lockForUpdate()->first();
            if (! $categoria || (! $categoria->activo && (! $material->exists || (int) $material->categoria_id !== (int) $categoria->id))) {
                throw ValidationException::withMessages(['categoria_id' => 'Selecciona una categoría activa.']);
            }
            $datos['categoria'] = $categoria->nombre;
            if ($material->exists && $material->unidad_id != $unidad->id && ($material->movimientos()->exists() || (Schema::hasTable('materiales_pedido') && \App\Models\MaterialPedido::where('material_id', $material->id)->exists()) || (Schema::hasTable('materiales_cotizacion') && $material->materialesCotizacion()->exists()) || (Schema::hasTable('detalles_cotizacion') && \App\Models\DetalleCotizacion::where('material_principal_id', $material->id)->exists()))) {
                throw ValidationException::withMessages(['unidad_id' => 'La unidad base ya tiene historial. Registra otro material en lugar de convertir su saldo.']);
            }
            $datos['precio_referencia'] = (string) Decimal::numero($datos['precio_referencia'], 6, 'precio_referencia');
            // Los umbrales antiguos se conservan sin formar parte del formulario.
            unset($datos['stock_minimo']);
            $anterior = $material->exists ? $material->only($material->getFillable()) : null;
            if (array_key_exists('nombre', $datos)) {
                $material->nombre = $datos['nombre'];
            }
            if (array_key_exists('descripcion', $datos)) {
                $material->descripcion = $datos['descripcion'];
            }
            if (array_key_exists('categoria', $datos)) {
                $material->categoria = $datos['categoria'];
            }
            if (array_key_exists('categoria_id', $datos)) {
                $material->categoria_id = $datos['categoria_id'];
            }
            if (array_key_exists('especificaciones', $datos)) {
                $material->especificaciones = $datos['especificaciones'];
            }
            if (array_key_exists('unidad_id', $datos)) {
                $material->unidad_id = $datos['unidad_id'];
            }
            if (array_key_exists('precio_referencia', $datos)) {
                $material->precio_referencia = $datos['precio_referencia'];
            }
            if (array_key_exists('activo', $datos)) {
                $material->activo = $datos['activo'];
            }
            $material->save();
            if ($anterior !== null && $anterior !== $material->only($material->getFillable())) {
                (new CambioMaterial)->forceFill(['material_id' => $material->id, 'usuario_id' => $actor->id, 'anterior' => $anterior, 'posterior' => $material->only($material->getFillable())])->save();
            }

            app(AuditoriaService::class)->registrar($actor, $material, $anterior === null ? 'creado' : 'modificado', $anterior ?? [], app(AuditoriaService::class)->datos($material));

            return $material;
        });
    }
}
