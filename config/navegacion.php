<?php

return [
    'PRINCIPAL' => [
        ['texto' => 'Panel principal', 'icono' => 'fas fa-th-large', 'permiso' => 'panel.consultar', 'ruta' => 'admin.index'],
    ],
    'OPERACIONES' => [
        ['texto' => 'Existencias de materiales', 'icono' => 'fas fa-cubes', 'permiso' => 'materiales.consultar', 'ruta' => 'admin.existencias.index'],
        ['texto' => 'Cotizaciones', 'icono' => 'fas fa-file-invoice', 'permiso' => 'cotizaciones.consultar', 'ruta' => 'admin.cotizaciones.index'],
        ['texto' => 'Pedidos', 'icono' => 'fas fa-hammer', 'permiso' => 'pedidos.consultar', 'ruta' => 'admin.pedidos.index'],
        ['texto' => 'Planificación de materiales', 'icono' => 'fas fa-tasks', 'permiso' => 'planificacion.consultar', 'ruta' => 'admin.planificacion.index'],
        ['texto' => 'Clientes', 'icono' => 'fas fa-user-friends', 'permiso' => 'clientes.consultar', 'ruta' => 'admin.clientes.index'],
    ],
    'CATÁLOGO' => [
        ['texto' => 'Materiales', 'icono' => 'fas fa-cubes', 'permiso' => 'materiales.consultar', 'ruta' => 'admin.materiales.index'],
        ['texto' => 'Categorías', 'icono' => 'fas fa-tags', 'permiso' => 'materiales.consultar', 'ruta' => 'admin.categorias.index'],
        ['texto' => 'Tipos de muebles', 'icono' => 'fas fa-chair', 'permiso' => 'cotizaciones.consultar', 'ruta' => 'admin.tipos_mueble.index'],
    ],
    'INTELIGENCIA' => [
        ['texto' => 'Casos históricos', 'icono' => 'fas fa-layer-group', 'permiso' => 'casos.consultar', 'ruta' => 'admin.casos.index'],
    ],
    'ADMINISTRACIÓN' => [
        ['texto' => 'Pagos', 'icono' => 'fas fa-wallet', 'permiso' => 'pagos.consultar', 'ruta' => 'admin.pagos.index'],
        ['texto' => 'Reportes', 'icono' => 'fas fa-chart-pie', 'permiso' => 'reportes.administrativos', 'ruta' => 'admin.reportes.index'],
        ['texto' => 'Usuarios', 'icono' => 'fas fa-users-cog', 'permiso' => 'usuarios.gestionar', 'ruta' => 'admin.usuarios.index'],
        ['texto' => 'Configuración', 'icono' => 'fas fa-sliders-h', 'permiso' => 'configuracion.gestionar', 'ruta' => 'admin.ajustes.index'],
    ],
];
