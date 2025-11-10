<?php

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false) ? $default : $v;
}

define('DB_DSN', env('DB_DSN', 'mysql:host=127.0.0.1;dbname=cecnsr;charset=utf8mb4'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('APP_KEY', env('APP_KEY', 'please-generate-a-secure-random-key'));
