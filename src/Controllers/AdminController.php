<?php

namespace App\Controllers;

use App\Repositories\EstudianteRepository;

class AdminController
{
    public function home($req, $res)
    {
        header('Location: ./registros');
        return $res;
    }

    public function list($req, $res)
    {
        // Filtros básicos: q (nombre, correo, dui), grado
        $q     = trim($_GET['q'] ?? '');
        $grado = trim($_GET['grado'] ?? '');

        $repo  = new EstudianteRepository();
        [$rows, $total] = $repo->search($q, $grado, 100); // pág. simple 100

        // Render mínimo sin plantilla (adáptalo a tu motor)
        echo "<h1>Registros</h1>";
        echo '<form method="get"><input name="q" value="' . htmlspecialchars($q) . '">
              <select name="grado">
                <option value="">Todos los grados</option>' .
            $this->gradoOptions($grado) . '
              </select>
              <button>Filtrar</button>
              <a href="export?q=' . urlencode($q) . '&grado=' . urlencode($grado) . '">Exportar CSV</a>
              </form>';

        echo "<p>Total: {$total}</p><table border=1 cellpadding=6>
              <tr><th>Alumno</th><th>Grado</th><th>Responsable</th><th>DUI</th><th>Correo</th><th>Tel</th><th>Creado</th></tr>";
        foreach ($rows as $r) {
            echo '<tr>
                <td>' . htmlspecialchars($r['alumno']) . '</td>
                <td>' . htmlspecialchars($r['grado']) . '</td>
                <td>' . htmlspecialchars($r['responsable']) . '</td>
                <td>' . htmlspecialchars($r['dui']) . '</td>
                <td>' . htmlspecialchars($r['correo']) . '</td>
                <td>' . htmlspecialchars($r['telefono']) . '</td>
                <td>' . htmlspecialchars($r['created_at']) . '</td>
            </tr>';
        }
        echo "</table>";
        return $res;
    }

    public function exportCsv($req, $res)
    {
        $q     = trim($_GET['q'] ?? '');
        $grado = trim($_GET['grado'] ?? '');

        $repo  = new EstudianteRepository();
        [$rows] = $repo->search($q, $grado, 100000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=registros.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Alumno', 'Grado', 'Responsable', 'DUI', 'Correo', 'Telefono', 'Direccion', 'Municipio', 'Departamento', 'Creado']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['alumno'],
                $r['grado'],
                $r['responsable'],
                $r['dui'],
                $r['correo'],
                $r['telefono'],
                $r['direccion'],
                $r['municipio'],
                $r['departamento'],
                $r['created_at']
            ]);
        }
        fclose($out);
        return $res;
    }

    private function gradoOptions(string $sel): string
    {
        $g = ['1°', '2°', '3°', '4°', '5°', '6°'];
        $html = '';
        foreach ($g as $x) {
            $html .= '<option ' . ($x === $sel ? 'selected' : '') . '>' . $x . '</option>';
        }
        return $html;
    }
}
