<?php

return [
    // Mantener desactivado hasta configurar y comprobar un transporte SMTP real.
    'recuperacion_habilitada' => (bool) env('AUTH_PASSWORD_RESET_ENABLED', false),
];
