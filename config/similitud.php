<?php

return [
    'habilitado' => true,
    'distancia' => 'gower_ponderada',
    'normalizacion' => 'rangos_fijos_por_tipo',
    'maximo_resultados' => 3,
    'pesos' => ['largo_cm' => 10, 'ancho_cm' => 10, 'alto_cm' => 10, 'material_principal_id' => 30, 'acabado' => 15, 'puertas' => 7, 'cajones' => 7, 'divisiones' => 6, 'cantidad_muebles' => 5],
    'k' => 3,
    'umbral' => '70',
    'perfil' => ['archivo' => 'perfiles/similitud-casos-historicos-v4.json', 'sha256' => '747682978e140d3c8e550f031c0f98c26c987fc483b29128a7ddd352917f2010'],
];
