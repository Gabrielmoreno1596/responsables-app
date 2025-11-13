<?php
// public/api/guardar.php  (o tu ruta Slim equivalente)
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // ... usa Repositorios/Servicios, inserta en responsables/estudiantes ...
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']); // mensaje neutro
}
