<?php

return [
    // Carga de datos por módulo para la instalación local.
    'habilitado' => env('TAJIBO_DATOS_SISTEMA', env('TAJIBO_DATOS_EJEMPLO', env('APP_ENV', 'production') === 'local')),
];
