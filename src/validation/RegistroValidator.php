<?php

namespace App\Validation;

final class RegistroValidator
{
    public static function validate(array $raw): array
    {
        $e = $raw['estudiante']   ?? [];
        $r = $raw['responsable']  ?? [];

        $errors = [];
        $estudiante = [
            'nombre' => trim($e['nombre'] ?? ''),
            'grado'  => trim($e['grado']  ?? ''),
        ];
        if ($estudiante['nombre'] === '') $errors['estudiante.nombre'] = 'Obligatorio';
        if ($estudiante['grado']  === '') $errors['estudiante.grado']  = 'Obligatorio';

        $responsable = [
            'nombre'      => trim($r['nombre']      ?? ''),
            'dui'         => trim($r['dui']         ?? ''),
            'telefono'    => trim($r['telefono']    ?? ''),
            'correo'      => trim($r['correo']      ?? ''),
            'direccion'   => trim($r['direccion']   ?? ''),
            'municipio'   => trim($r['municipio']   ?? ''),
            'departamento' => trim($r['departamento'] ?? ''),
        ];
        if ($responsable['nombre'] === '') $errors['responsable.nombre'] = 'Obligatorio';
        if ($responsable['correo'] !== '' && !filter_var($responsable['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors['responsable.correo'] = 'Correo inválido';
        }
        if ($responsable['dui'] !== '' && !preg_match('/^\d{8}-\d$/', $responsable['dui'])) {
            $errors['responsable.dui'] = 'Formato DUI inválido';
        }

        return [empty($errors), ['estudiante' => $estudiante, 'responsable' => $responsable], $errors];
    }
}
