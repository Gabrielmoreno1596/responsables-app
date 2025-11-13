<?php

declare(strict_types=1);

namespace CECNSR;

use Slim\Factory\AppFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use CECNSR\Controllers\AuthController;
use CECNSR\Controllers\EstudianteController;
use CECNSR\Middleware\AuthMiddleware;
use CECNSR\Repositories\EstudianteRepository;
use CECNSR\ResponseHelper;

class App
{
    private \Slim\App $app;

    public function __construct()
    {
        // 1) ENV + settings
        require_once __DIR__ . '/../config/env.php';

        // En prod no mostramos trazas; en local sí (ajusta si lo prefieres)
        $isLocal = (defined('APP_ENV') && APP_ENV === 'local');
        $displayErrorDetails = $isLocal;

        // 2) App
        $this->app = AppFactory::create();

        // 2.1) BasePath dinámico (CRÍTICO para subcarpeta)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath   = rtrim(str_replace('\\', '/', \dirname($scriptName)), '/');
        $this->app->setBasePath($basePath === '/' ? '' : $basePath);

        // 3) Middlewares básicos
        $this->app->addBodyParsingMiddleware();     // JSON / x-www-form-urlencoded
        $this->app->addRoutingMiddleware();         // routing (Slim 4)

        // 3.1) Headers de seguridad (HSTS solo con HTTPS real)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        $this->app->add(function ($request, $handler) use ($isHttps) {
            $response = $handler->handle($request);

            if ($isHttps) {
                $response = $response->withHeader(
                    'Strict-Transport-Security',
                    'max-age=31536000; includeSubDomains; preload'
                );
            }

            return $response
                ->withHeader('X-Frame-Options', 'DENY')
                ->withHeader('X-Content-Type-Options', 'nosniff')
                ->withHeader('Referrer-Policy', 'no-referrer')
                ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
                ->withHeader(
                    'Content-Security-Policy',
                    "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; " .
                        "script-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'"
                );
        });

        // 4) Logger + error middleware
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../storage/logs/app.log', Logger::DEBUG));

        $errorMw = $this->app->addErrorMiddleware($displayErrorDetails, true, true, $logger);
        // $errorMw->setDefaultErrorHandler(...) // si quieres handler propio

        // 5) RUTAS PÚBLICAS BÁSICAS
        $this->app->get('/api/health', function ($req, $res) {
            $res->getBody()->write(json_encode(['ok' => true]));
            return $res->withHeader('Content-Type', 'application/json');
        });

        $this->app->get('/api/whoami', function ($req, $res) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
                session_start([
                    'cookie_path'     => '/',
                    'cookie_httponly' => true,
                    'cookie_samesite' => 'Lax',
                    'cookie_secure'   => $isHttps, // true en prod con SSL, false en local
                ]);
            }
            session_regenerate_id(true);

            $payload = [
                'sid'     => session_id(),
                'user_id' => $_SESSION['user_id'] ?? null,
                'role'    => $_SESSION['role'] ?? null,
            ];
            $res->getBody()->write(json_encode($payload));
            return $res->withHeader('Content-Type', 'application/json');
        });

        // Login API (pública)
        $this->app->post('/api/login', [AuthController::class, 'login']);

        // Logout (pública, invalida cookie de sesión)
        $this->app->post('/api/logout', function ($req, $res) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'] ?? '/',
                    $params['domain'] ?? '',
                    (bool)($params['secure'] ?? false),
                    (bool)($params['httponly'] ?? true)
                );
            }
            session_destroy();
            return ResponseHelper::json($res, ['message' => 'ok'], 200);
        });

        // ======== RUTA RAÍZ: redirige al login ========
        $this->app->get('/', function ($req, $res) {
            return $res
                ->withHeader('Location', 'login')
                ->withStatus(302);
        });

        // ======== PÚBLICO: página form + endpoint registro ========
        $this->app->get('/form', function ($req, $res) {
            return $res
                ->withHeader('Location', 'form.php')
                ->withStatus(302);
        });

        $this->app->post('/api/registro-publico', function ($req, $res) {
            $b = $req->getParsedBody() ?: [];

            // Honeypot
            if (!empty($b['website'] ?? '')) {
                return ResponseHelper::json($res, ['message' => 'ok'], 200);
            }

            // Validación mínima
            $eNombre = trim((string)($b['estudiante_nombre'] ?? ''));
            $rNombre = trim((string)($b['responsable_nombre'] ?? ''));
            $grado   = trim((string)($b['grado'] ?? ''));

            $errores = [];
            if ($eNombre === '') $errores['estudiante_nombre'] = 'Requerido';
            if ($rNombre === '') $errores['responsable_nombre'] = 'Requerido';
            if ($errores) {
                return ResponseHelper::json($res, ['error' => 'Validación', 'fields' => $errores], 422);
            }

            $e = ['nombre' => $eNombre, 'grado' => $grado];
            $r = [
                'nombre'       => $rNombre,
                'dui'          => trim((string)($b['dui'] ?? '')),
                'telefono'     => trim((string)($b['telefono'] ?? '')),
                'correo'       => trim((string)($b['correo'] ?? '')),
                'direccion'    => trim((string)($b['direccion'] ?? '')),
                'municipio'    => trim((string)($b['municipio'] ?? '')),
                'departamento' => trim((string)($b['departamento'] ?? '')),
            ];

            try {
                $repo = new EstudianteRepository();
                $id   = $repo->create($e, $r); // upsert responsable + crea estudiante
                return ResponseHelper::json($res, ['message' => 'creado', 'id' => $id], 201);
            } catch (\Throwable $ex) {
                return ResponseHelper::json($res, ['error' => 'DB error'], 500);
            }
        });
        // ======== FIN público ========

        // 6) Rutas PROTEGIDAS (grupo /api con AuthMiddleware)
        $this->app->group('/api', function ($group) {
            $group->get('/estudiantes', [EstudianteController::class, 'search']);
            $group->post('/estudiantes', [EstudianteController::class, 'create']);
        })->add(new AuthMiddleware());

        // 7) VISTAS PRIVADAS
        // Login: redirige a login.php
        $this->app->get('/login', function ($req, $res) {
            return $res
                ->withHeader('Location', 'login.php')
                ->withStatus(302);
        });

        // Buscador: redirige a buscador.php
        $this->app->get('/buscador', function ($req, $res) {
            return $res
                ->withHeader('Location', 'buscador.php')
                ->withStatus(302);
        });

        $this->app->get('/admin/estudiante-nuevo', function ($req, $res) {
            $html = @file_get_contents(__DIR__ . '/../public/admin/estudiante-nuevo.html') ?: '<h1>Estudiante Nuevo</h1>';
            $res->getBody()->write($html);
            return $res->withHeader('Content-Type', 'text/html');
        });
    }

    public function get(): \Slim\App
    {
        return $this->app;
    }
}
