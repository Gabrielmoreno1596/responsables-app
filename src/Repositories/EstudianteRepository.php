<?php
// src/Repositories/EstudianteRepository.php
declare(strict_types=1);

namespace CECNSR\Repositories;

use CECNSR\Database;
use PDO;

class EstudianteRepository
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        // Si no te inyectan uno, usa el de tu helper Database
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function search(string $q = '', string $grado = '', int $limit = 10, int $offset = 0): array
    {
        $base = "FROM estudiantes e
             JOIN responsables r ON r.id = e.responsable_id
             WHERE 1=1";
        $params = [];

        if ($q !== '') {
            $like = "%{$q}%";
            $base .= " AND (e.nombre LIKE :q1 OR r.nombre LIKE :q2 OR r.dui LIKE :q3 OR r.correo LIKE :q4)";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }
        if ($grado !== '') {
            $base .= " AND e.grado = :g";
            $params[':g'] = $grado;
        }

        // total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) {$base}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // page
        $sql = "SELECT e.id, e.nombre AS alumno, e.grado, e.created_at,
                   r.nombre AS responsable, r.dui, r.correo, r.telefono,
                   r.direccion, r.municipio, r.departamento
            {$base}
            ORDER BY e.created_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [$stmt->fetchAll(\PDO::FETCH_ASSOC), $total];
    }


    /**
     * Variante B: reutiliza responsable sólo si existe con el mismo DUI.
     * Si no hay DUI o no existe, inserta SIEMPRE un nuevo responsable.
     */
    public function upsertResponsable(array $r): int
    {
        $dui = trim($r['dui'] ?? '');

        // 1) Buscar por DUI si viene
        if ($dui !== '') {
            $find = $this->pdo->prepare("SELECT id FROM responsables WHERE dui = :dui LIMIT 1");
            $find->execute([':dui' => $dui]);
            $row = $find->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return (int)$row['id'];
            }
        }

        // 2) Insertar siempre (sin deduplicar por correo)
        $stmt = $this->pdo->prepare("
            INSERT INTO responsables
                (nombre, dui, telefono, correo, direccion, municipio, departamento)
            VALUES
                (:nombre, :dui, :telefono, :correo, :direccion, :municipio, :departamento)
        ");
        $stmt->execute([
            ':nombre'       => trim($r['nombre']       ?? ''),
            ':dui'          => $dui,
            ':telefono'     => trim($r['telefono']     ?? ''),
            ':correo'       => trim($r['correo']       ?? ''),
            ':direccion'    => trim($r['direccion']    ?? ''),
            ':municipio'    => trim($r['municipio']    ?? ''),
            ':departamento' => trim($r['departamento'] ?? ''),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function create(array $e, array $r): int
    {
        $this->pdo->beginTransaction();
        try {
            $rid = $this->upsertResponsable($r);

            $insE = $this->pdo->prepare(
                "INSERT INTO estudiantes (nombre, grado, responsable_id)
                 VALUES (:n, :g, :rid)"
            );
            $insE->execute([
                ':n'   => $e['nombre'] ?? '',
                ':g'   => $e['grado']  ?? '',
                ':rid' => $rid,
            ]);

            $id = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $ex) {
            $this->pdo->rollBack();
            throw $ex;
        }
    }
}
