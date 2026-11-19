<?php

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\User;
use Spatie\Permission\Models\Role;

return [
    'tipos' => [App\Models\Pedido::class => 'Pedido', App\Models\Cotizacion::class => 'Cotización', App\Models\Ajuste::class => 'Ajustes', Cliente::class => 'Cliente', Material::class => 'Material', Categoria::class => 'Categoría', User::class => 'Usuario', Role::class => 'Rol'],
    'campos' => ['nombre' => 'Nombre', 'name' => 'Nombre', 'tipo' => 'Tipo', 'telefono' => 'Teléfono', 'email' => 'Correo', 'direccion' => 'Dirección', 'observaciones' => 'Observaciones', 'activo' => 'Estado', 'categoria' => 'Categoría registrada', 'categoria_id' => 'Categoría (ID)', 'unidad_id' => 'Unidad (ID)', 'precio_referencia' => 'Precio de referencia (Bs)', 'stock_minimo' => 'Umbral de stock', 'descripcion' => 'Descripción', 'especificaciones' => 'Especificaciones', 'factor' => 'Contenido por presentación', 'rol' => 'Rol', 'permisos' => 'Permisos'],
];
