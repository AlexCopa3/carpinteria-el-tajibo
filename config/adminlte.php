<?php

$config = require __DIR__.'/../vendor/jeroennoten/laravel-adminlte/config/adminlte.php';

$config['title'] = 'Carpintería El Tajibo';
$config['logo'] = 'El Tajibo';
$config['logo_img'] = null;
$config['dashboard_url'] = 'admin';
$config['use_route_url'] = false;
$config['usermenu_enabled'] = false;
$config['register_url'] = false;
$config['login_url'] = 'login';
$config['password_reset_url'] = false;
$config['password_email_url'] = false;
$config['preloader']['enabled'] = false;
$config['google_fonts']['allowed'] = false;
$config['menu'] = [];
$config['plugins'] = [
    'Sweetalert2' => [
        'active' => true,
        'files' => [
            ['type' => 'js', 'asset' => true, 'location' => 'vendor/sweetalert2/sweetalert2.all.min.js'],
            ['type' => 'css', 'asset' => true, 'location' => 'vendor/sweetalert2/sweetalert2.min.css'],
        ],
    ],
];
// AdminLTE carga sus assets Bootstrap 4. Vite agrega solo nuestras personalizaciones.
$config['laravel_asset_bundling'] = false;

return $config;
