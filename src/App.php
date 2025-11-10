<?php

declare(strict_types=1);

namespace CECNSR;

use Slim\Factory\AppFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use CECNSR\Controllers\AuthController;
use CECNSR\Controllers\EstudianteController;
use CECNSR\Middleware\AuthMiddleware;

class App
{
    private \Slim\App $app;

    public function __construct()
    {
        // 1) ENV + settings
        require_once __DIR__ . '/../config/env.php';
        $settings = [
            'displayErrorDetails' => true, // en dev
        ];

        // 2) App + base path
        $this->app = AppFactory::create();
        $this->app->setBasePath('/responsables-app/public');

        // 3) Body parser (POST forms / JSON)
        $this->app->addBodyParsingMiddleware();

        // 4) Logger + error middleware
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../storage/logs/app.log', Logger::DEBUG));
        $this->app->addErrorMiddleware($settings['displayErrorDetails'] ?? false, true, true, $logger);

        // 5) RUTAS PÚBLICAS
        $this->app->get('/api/health', function ($req, $res) {
            $res->getBody()->write(json_encode(['ok' => true]));
            return $res->withHeader('Content-Type', 'application/json');
        });

        $this->app->get('/api/whoami', function ($req, $res) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start([
                    'cookie_path'     => '/',   // cookie válida para todo localhost
                    'cookie_httponly' => true,
                    'cookie_samesite' => 'Lax',
                ]);
            }
            $payload = [
                'sid'     => session_id(),
                'user_id' => $_SESSION['user_id'] ?? null,
                'role'    => $_SESSION['role'] ?? null,
            ];
            $res->getBody()->write(json_encode($payload));
            return $res->withHeader('Content-Type', 'application/json');
        });

        // Login API
        $this->app->post('/api/login', [AuthController::class, 'login']);

        // 6) RUTAS PROTEGIDAS (UN SOLO GRUPO /api)
        $this->app->group('/api', function ($group) {
            $group->get('/estudiantes', [EstudianteController::class, 'search']);
            $group->post('/estudiantes', [EstudianteController::class, 'create']);
        })->add(new AuthMiddleware());

        // 7) VISTAS
        $this->app->get('/login', function ($req, $res) {
            $html = file_get_contents(__DIR__ . '/../public/login.html');
            $res->getBody()->write($html);
            return $res->withHeader('Content-Type', 'text/html');
        });

        $this->app->get('/buscador', function ($req, $res) {
            $html = file_get_contents(__DIR__ . '/../public/buscador.html');
            $res->getBody()->write($html);
            return $res->withHeader('Content-Type', 'text/html');
        });

        $this->app->get('/admin/estudiante-nuevo', function ($req, $res) {
            $html = file_get_contents(__DIR__ . '/../public/admin/estudiante-nuevo.html');
            $res->getBody()->write($html);
            return $res->withHeader('Content-Type', 'text/html');
        });
    }

    public function get(): \Slim\App
    {
        return $this->app;
    }
}
