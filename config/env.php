<?php
// config/env.php

// === MODO (dev|prod) ===
if (!defined('APP_ENV')) define('APP_ENV', getenv('APP_ENV') ?: 'dev');

// === DB ===
if (!defined('DB_DSN'))  define('DB_DSN',  getenv('DB_DSN')  ?: 'mysql:host=127.0.0.1;dbname=cecnsr;charset=utf8mb4');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');

// === BASE_URL opcional (si lo usas) ===
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
    $projDir = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/');
    $rel = ($docRoot && str_starts_with($projDir, $docRoot)) ? trim(substr($projDir, strlen($docRoot)), '/') : '';
    define('BASE_URL', '/' . ($rel ? $rel . '/' : ''));
}

// === errores: mostrar en dev, ocultar en prod ===
if (APP_ENV === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}



// para el hosting
/* define('DB_DSN',  'mysql:host=localhost;dbname=cecnsr_prod;charset=utf8mb4');
define('DB_USER', 'cecnsr_app');
define('DB_PASS', 'PON_AQUI_TU_PASSWORD_FUERTE');
define('APP_KEY', 'PEGA_AQUI_un_bin2hex(random_bytes(32))'); */ // genera en tu PC o en PHP CLI

// para el localhost

/* define('DB_DSN', env('DB_DSN', 'mysql:host=127.0.0.1;dbname=cecnsr;charset=utf8mb4'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('APP_KEY', env('APP_KEY', 'please-generate-a-secure-random-key')); */

//nueva para el hosting es para producción

/* define('DB_DSN',  'mysql:host=localhost;dbname=cecnsr_prod;charset=utf8mb4');
define('DB_USER', 'cecnsr_app');          // el usuario MySQL que crees en cPanel
define('DB_PASS', 'CONTRASEÑA_FUERTE');
define('APP_KEY', 'TU_CLAVE_PROD');   */     // otra clave distinta a la local
