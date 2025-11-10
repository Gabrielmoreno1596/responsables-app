<?php
// src/Repositories/EstudianteRepository.php
declare(strict_types=1);

namespace CECNSR\Repositories;

use CECNSR\Database;
use PDO;

class EstudianteRepository
{
    public function searchByName(string $name, int $page = 1, int $size = 20): array
    {
        $pdo   = Database::pdo();
        $name  = trim($name);
        $offset = max(0, ($page - 1) * $size);

        // SIN filtro -> lista simple
        if ($name === '') {
            $sql = "
          SELECT e.id, e.nombre AS estudiante, e.grado,
                 r.id AS responsable_id, r.nombre AS responsable, r.telefono, r.correo
          FROM estudiantes e
          LEFT JOIN responsables r ON r.id = e.responsable_id
          ORDER BY e.id DESC
          LIMIT :lim OFFSET :off";
            $st = $pdo->prepare($sql);
            $st->bindValue(':lim', $size, \PDO::PARAM_INT);
            $st->bindValue(':off', $offset, \PDO::PARAM_INT);
            $st->execute();
            $items = $st->fetchAll(\PDO::FETCH_ASSOC);

            $total = (int)$pdo->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
            return ['items' => $items, 'total' => $total, 'page' => $page, 'size' => $size];
        }

        // CON filtro -> solo por nombre del estudiante
        // CON filtro -> solo por nombre del estudiante
        $plain = $name;
        $like  = '%' . $plain . '%';
        $pref  = $plain . '%';

        // Sanea números y los inyecta (no se bindean)
        $size   = max(1, min(100, (int)$size));
        $offset = max(0, (int)$offset);

        $sql = "
  SELECT e.id, e.nombre AS estudiante, e.grado,
         r.id AS responsable_id, r.nombre AS responsable, r.telefono, r.correo,
         /* flags de relevancia */
         (e.nombre = :exact) AS is_exact,
         (e.nombre LIKE :prefix) AS is_prefix
  FROM estudiantes e
  LEFT JOIN responsables r ON r.id = e.responsable_id
  WHERE e.nombre LIKE :like
  ORDER BY is_exact DESC, is_prefix DESC, e.nombre ASC
  LIMIT $size OFFSET $offset";

        $st = $pdo->prepare($sql);
        $st->bindValue(':exact',  $plain);
        $st->bindValue(':prefix', $pref);
        $st->bindValue(':like',   $like);
        $st->execute();
        $items = $st->fetchAll(\PDO::FETCH_ASSOC);

        $ct = $pdo->prepare("SELECT COUNT(*) FROM estudiantes WHERE nombre LIKE :like");
        $ct->bindValue(':like', $like);
        $ct->execute();
        $total = (int)$ct->fetchColumn();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'size' => $size];
    }




    public function upsertResponsable(array $r): int
    {
        $pdo = Database::pdo();

        // 1) Buscar si ya existe por DUI o Correo (sin reutilizar placeholders)
        $find = $pdo->prepare("
    SELECT id
      FROM responsables
     WHERE ((:dui_chk <> '' AND dui = :dui_match)
        OR (:correo_chk <> '' AND correo = :correo_match))
     LIMIT 1
");
        $find->execute([
            ':dui_chk'      => $r['dui']    ?? '',
            ':dui_match'    => $r['dui']    ?? '',
            ':correo_chk'   => $r['correo'] ?? '',
            ':correo_match' => $r['correo'] ?? '',
        ]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int)$row['id'];


        // 2) Insertar si no existía
        $ins = $pdo->prepare("
        INSERT INTO responsables (nombre, dui, telefono, correo, direccion, municipio, departamento)
        VALUES (:nombre, :dui, :telefono, :correo, :direccion, :municipio, :departamento)
    ");
        $ins->execute([
            ':nombre'       => $r['nombre']       ?? '',
            ':dui'          => $r['dui']          ?? '',
            ':telefono'     => $r['telefono']     ?? '',
            ':correo'       => $r['correo']       ?? '',
            ':direccion'    => $r['direccion']    ?? '',
            ':municipio'    => $r['municipio']    ?? '',
            ':departamento' => $r['departamento'] ?? '',
        ]);

        return (int)$pdo->lastInsertId();
    }


    public function create(array $e, array $r): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $rid = $this->upsertResponsable($r);
            $insE = $pdo->prepare("INSERT INTO estudiantes (nombre, grado, responsable_id) VALUES (:n,:g,:rid)");
            $insE->execute([
                ':n'   => $e['nombre'] ?? '',
                ':g'   => $e['grado'] ?? '',
                ':rid' => $rid,
            ]);
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (\Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }
}
