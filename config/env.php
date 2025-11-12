<?php

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false) ? $default : $v;
}

// para el hosting
define('DB_DSN',  'mysql:host=localhost;dbname=cecnsr_prod;charset=utf8mb4');
define('DB_USER', 'cecnsr_app');
define('DB_PASS', 'PON_AQUI_TU_PASSWORD_FUERTE');
define('APP_KEY', 'PEGA_AQUI_un_bin2hex(random_bytes(32))'); // genera en tu PC o en PHP CLI

// para el localhost

/* define('DB_DSN', env('DB_DSN', 'mysql:host=127.0.0.1;dbname=cecnsr;charset=utf8mb4'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('APP_KEY', env('APP_KEY', 'please-generate-a-secure-random-key')); */
