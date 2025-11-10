<?php

declare(strict_types=1);

namespace CECNSR\Support;

function start_session_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    session_start([
        'cookie_path'     => '/',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => $isHttps, // true solo cuando de verdad estás bajo HTTPS
    ]);
}
