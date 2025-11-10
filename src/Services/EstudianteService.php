<?php
// src/Services/EstudianteService.php

declare(strict_types=1);

namespace CECNSR\Services;

use CECNSR\Database;
use PDO;

class EstudianteService
{
    public function search(string $q, int $page, int $limit): array
    {
        $page  = max(1, $page);
        $limit = min(50, max(1, $limit));
        $off   = ($page - 1) * $limit;

        $like = '%' . mb_strtolower($q) . '%';

        $pdo = Database::pdo();
        $sql = "
            SELECT e.id,
                   e.nombre AS estudiante,
                   e.grado,
                   r.nombre AS responsable,
                   r.dui,
                   r.telefono,
                   r.correo
            FROM estudiantes e
            LEFT JOIN responsables r ON r.id = e.responsable_id
            WHERE LOWER(e.nombre)      LIKE :q
               OR LOWER(IFNULL(r.correo,'')) LIKE :q
               OR LOWER(IFNULL(r.dui,''))    LIKE :q
            ORDER BY e.created_at DESC
            LIMIT :lim OFFSET :off
        ";
        $st = $pdo->prepare($sql);
        $st->bindValue(':q',   $like);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $off,   PDO::PARAM_INT);
        $st->execute();

        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rows' => $rows,
            'meta' => [
                'page'  => $page,
                'limit' => $limit,
            ],
        ];
    }

    public function createWithResponsable(array $data): int
    {
        // TODO: validar datos
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        // 1. Insertar o reutilizar responsable
        $respId = $this->upsertResponsable($pdo, [
            'nombre'    => $data['responsable_nombre'] ?? '',
            'dui'       => $data['dui'] ?? '',
            'telefono'  => $data['telefono'] ?? '',
            'correo'    => $data['correo'] ?? '',
            'direccion' => $data['direccion'] ?? '',
            'municipio' => $data['municipio'] ?? '',
            'departamento' => $data['departamento'] ?? '',
        ]);

        // 2. Insertar estudiante
        $st = $pdo->prepare("
            INSERT INTO estudiantes (nombre, grado, responsable_id)
            VALUES (:n, :g, :rid)
        ");
        $st->execute([
            ':n'   => $data['estudiante_nombre'] ?? '',
            ':g'   => $data['grado'] ?? '',
            ':rid' => $respId,
        ]);

        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        return $id;
    }

    private function upsertResponsable(PDO $pdo, array $r): int
    {
        // Estrategia simple:
        // ¿Ya existe responsable con este DUI o correo?
        $st = $pdo->prepare("
            SELECT id FROM responsables
            WHERE (dui = :dui AND :dui <> '')
               OR (correo = :correo AND :correo <> '')
            LIMIT 1
        ");
        $st->execute([
            ':dui'    => $r['dui'],
            ':correo' => $r['correo'],
        ]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // TODO: podrías actualizar datos aquí si quieres mantenerlos frescos
            return (int)$row['id'];
        }

        $ins = $pdo->prepare("
            INSERT INTO responsables
              (nombre, dui, telefono, correo, direccion, municipio, departamento)
            VALUES (:nombre, :dui, :tel, :correo, :dir, :mun, :dep)
        ");
        $ins->execute([
            ':nombre' => $r['nombre'],
            ':dui'    => $r['dui'],
            ':tel'    => $r['telefono'],
            ':correo' => $r['correo'],
            ':dir'    => $r['direccion'],
            ':mun'    => $r['municipio'],
            ':dep'    => $r['departamento'],
        ]);

        return (int)$pdo->lastInsertId();
    }
}
