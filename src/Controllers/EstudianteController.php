<?php

declare(strict_types=1);

namespace CECNSR\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CECNSR\Repositories\EstudianteRepository;
use CECNSR\ResponseHelper;

class EstudianteController
{
    public function search(Request $req, Response $res): Response
    {
        $p    = $req->getQueryParams();
        $name = trim((string)($p['name'] ?? ''));       // <— solo nombre
        $page = max(1, (int)($p['page'] ?? 1));
        $size = min(100, max(1, (int)($p['size'] ?? 20)));

        $repo = new EstudianteRepository();
        $data = $repo->searchByName($name, $page, $size);

        return ResponseHelper::json($res, [
            'data' => $data['items'],
            'meta' => ['total' => $data['total'], 'page' => $data['page'], 'size' => $data['size']]
        ]);
    }

    public function create(Request $req, Response $res): Response
    {
        $b = $req->getParsedBody();
        if (!is_array($b)) {
            return ResponseHelper::json($res, ['error' => 'body vacío'], 400);
        }

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
            $id = $repo->create($e, $r);
            return ResponseHelper::json($res, ['message' => 'creado', 'id' => $id], 201);
        } catch (\Throwable $ex) {
            // En dev: devuelve el mensaje real. En prod, cambia por uno genérico y loguéalo.
            return ResponseHelper::json($res, [
                'error'   => 'DB error',
                'message' => $ex->getMessage(), // <-- te dirá exactamente qué falló
            ], 500);
        }
    }
}
