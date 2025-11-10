<?php

namespace CECNSR\Middleware;


class CsrfToken
{
    public function __invoke($request, $handler)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        $response = $handler->handle($request);
        return $response->withHeader('X-CSRF', $_SESSION['csrf']);
    }
}
