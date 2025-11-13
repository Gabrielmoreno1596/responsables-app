<?php

declare(strict_types=1);

// 1) Cargar variables/errores
require_once __DIR__ . '/../config/env.php';
// 2) Autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use CECNSR\App;

try {
    $app = (new App())->get();
    $app->run();
} catch (Throwable $e) {
    // En DEV muestra el error para no ver “pantalla blanca”
    if (defined('APP_ENV') && APP_ENV === 'dev') {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Fatal error:\n\n", $e, "\n";
    } else {
        http_response_code(500);
        echo "Server error";
    }
}
