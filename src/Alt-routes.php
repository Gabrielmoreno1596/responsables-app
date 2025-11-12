<?php

use App\Controllers\PublicController;
use App\Controllers\AdminController;
use App\Middleware\AuthMiddleware;

/** Rutas públicas (sin login) */
$app->get('/form', [PublicController::class, 'showForm']);       // Página HTML pública
$app->post('/api/registro', [PublicController::class, 'submit']); // Recibir formulario

/** Rutas privadas (requiere login) */
$app->group('/admin', function ($group) {
    $group->get('', [AdminController::class, 'home']);
    $group->get('/registros', [AdminController::class, 'list']);
    $group->get('/export',   [AdminController::class, 'exportCsv']); // ?q=…&grado=… etc.
})->add(new AuthMiddleware());
