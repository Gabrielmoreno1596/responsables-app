<?php

declare(strict_types=1);

namespace CECNSR\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CECNSR\Services\AuthService;
use CECNSR\ResponseHelper;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            return ResponseHelper::json($response, ['error' => 'Cuerpo vacío: agrega addBodyParsingMiddleware()'], 400);
        }

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            return ResponseHelper::json($response, ['error' => 'Falta username o password'], 400);
        }

        $service = new AuthService();
        $user = $service->attempt($username, $password);

        if (!$user) {
            return ResponseHelper::json($response, ['error' => 'Credenciales inválidas'], 401);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            session_start([
                'cookie_path'     => '/',
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure'   => $isHttps, // true en prod con SSL; false en local
            ]);
        }
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role']    = (string)$user['role'];

        return ResponseHelper::json($response, ['message' => 'ok'], 200);
    }

    public function logout(Request $req, Response $res): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        return ResponseHelper::json($res, ['message' => 'ok'], 200);
    }
}
