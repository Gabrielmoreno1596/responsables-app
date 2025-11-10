<?php
// src/Middleware/AuthMiddleware.php

declare(strict_types=1);

namespace CECNSR\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use CECNSR\ResponseHelper;

class AuthMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_path'     => '/',   // <— igual que en login
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }

        if (empty($_SESSION['user_id'])) {
            return ResponseHelper::json(new \Slim\Psr7\Response(), ['error' => 'No autorizado'], 401);
        }
        return $handler->handle($request);
    }
}
