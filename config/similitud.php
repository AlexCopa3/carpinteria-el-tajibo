<?php

return [
    'habilitado' => true,
    'distancia' => 'gower_ponderada',
    'normalizacion' => 'rangos_fijos_por_tipo',
    'maximo_resultados' => 3,
    'pesos' => ['largo_cm' => 10, 'ancho_cm' => 10, 'alto_cm' => 10, 'material_principal_id' => 30, 'acabado' => 15, 'puertas' => 7, 'cajones' => 7, 'divisiones' => 6, 'cantidad_muebles' => 5],
    'k' => 3,
    'umbral' => '70',
    'perfil' => ['archivo' => 'perfiles/similitud-casos-historicos-v4.json', 'sha256' => '31e53b351733f5ada174f1061fe4f00b11457b76023fe46084c22cd5ec2f7349'],
];
