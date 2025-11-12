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
        $settings = [
            // Producción: NO mostrar trazas al cliente
            'displayErrorDetails' => false,
        ];

        // 2) App + base path
        $this->app = AppFactory::create();
        // Ajusta si tu subcarpeta cambia en localhost xampp
        /* $this->app->setBasePath('/responsables-app/public'); */

        //producción

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $this->app->setBasePath($basePath === '/' ? '' : $basePath);


        // 3) Body parser (POST forms / JSON)
        $this->app->addBodyParsingMiddleware();

        // 3.1) Middleware de cabeceras de seguridad (AGREGADO)
        $this->app->add(function ($request, $handler) {
            $response = $handler->handle($request);

            // OJO: HSTS solo bajo HTTPS real en producción.
            // Si aún estás en http://localhost, comenta la línea de Strict-Transport-Security.
            return $response
                ->withHeader('X-Frame-Options', 'DENY')
                ->withHeader('X-Content-Type-Options', 'nosniff')
                ->withHeader('Referrer-Policy', 'no-referrer')
                ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
                ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
                ->withHeader(
                    'Content-Security-Policy',
                    "default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'"


                );

            /* Este código es para producción */

            return $response
                ->withHeader('X-Frame-Options', 'DENY')
                ->withHeader('X-Content-Type-Options', 'nosniff')
                ->withHeader('Referrer-Policy', 'no-referrer')
                ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
                // HSTS solo si hay HTTPS
                ->withHeader(
                    'Strict-Transport-Security',
                    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        ? 'max-age=31536000; includeSubDomains; preload'
                        : ''
                )
                ->withHeader(
                    'Content-Security-Policy',
                    "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'"
                );
        });

        // 4) Logger + error middleware
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../storage/logs/app.log', Logger::DEBUG));
        $this->app->addErrorMiddleware($settings['displayErrorDetails'] ?? false, true, true, $logger);

        // 5) RUTAS PÚBLICAS BÁSICAS
        $this->app->get('/api/health', function ($req, $res) {
            $res->getBody()->write(json_encode(['ok' => true]));
            return $res->withHeader('Content-Type', 'application/json');
        });

        $this->app->get('/api/whoami', function ($req, $res) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                session_start([
                    'cookie_path'     => '/',
                    'cookie_httponly' => true,
                    'cookie_samesite' => 'Lax',
                    'cookie_secure'   => $isHttps, // true en producción con SSL, false en local
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

        // >>> RUTA NUEVA: LOGOUT (AGREGADO, no reemplaza nada)
        $this->app->post('/api/logout', function ($req, $res) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            // Limpia variables
            $_SESSION = [];

            // Expira cookie de sesión si existe
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            // Destruye la sesión
            session_destroy();

            return ResponseHelper::json($res, ['message' => 'ok'], 200);
        });
        // <<< FIN LOGOUT

        // ======== NUEVO: FORMULARIO PÚBLICO (SIN LOGIN) ========

        // Página pública del formulario
        $this->app->get('/form', function ($req, $res) {
            $file = __DIR__ . '/../public/form.html';
            if (!is_file($file)) {
                $res->getBody()->write('<h1>Formulario</h1><p>Falta public/form.html</p>');
                return $res->withStatus(200)->withHeader('Content-Type', 'text/html');
            }
            $html = file_get_contents($file);
            $res->getBody()->write($html);
            return $res->withHeader('Content-Type', 'text/html');
        });

        // Endpoint público para registrar datos (no requiere AuthMiddleware)
        $this->app->post('/api/registro-publico', function ($req, $res) {
            $b = $req->getParsedBody() ?: [];

            // Honeypot simple
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
                // create() ya hace upsert del responsable y crea el estudiante
                $id   = $repo->create($e, $r);
                return ResponseHelper::json($res, ['message' => 'creado', 'id' => $id], 201);
            } catch (\Throwable $ex) {
                // En producción: no revelar detalles
                return ResponseHelper::json($res, [
                    'error' => 'DB error'
                ], 500);
            }
        });

        // ======== FIN NUEVO ========

        // 6) RUTAS PROTEGIDAS (UN SOLO GRUPO /api)
        $this->app->group('/api', function ($group) {
            $group->get('/estudiantes', [EstudianteController::class, 'search']);
            $group->post('/estudiantes', [EstudianteController::class, 'create']);
        })->add(new AuthMiddleware());

        // 7) VISTAS PRIVADAS
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
