<?php

namespace App\Controllers;

use App\Repositories\EstudianteRepository;
use App\Validation\RegistroValidator;
use App\Services\RecaptchaService;
use PDO;

class PublicController
{
    public function showForm($req, $res)
    {
        // Render directo del form público (sin motor de plantillas)
        require __DIR__ . '/../../public/form.php';
        return $res;
    }

    public function submit($req, $res)
    {
        // Rechazar bots simples (honeypot)
        $body = $_POST ?? [];
        if (!empty($body['website'])) {
            return $this->json($res, ['ok' => true], 200);
        }

        // Validar campos
        [$ok, $data, $errors] = RegistroValidator::validate($body);
        if (!$ok) {
            return $this->json($res, ['ok' => false, 'errors' => $errors], 422);
        }

        // Verificar reCAPTCHA si está configurado
        if (getenv('RECAPTCHA_SECRET_KEY')) {
            $captcha = $_POST['g-recaptcha-response'] ?? '';
            if (!(new RecaptchaService())->verify($captcha)) {
                return $this->json($res, ['ok' => false, 'errors' => ['captcha' => 'Verificación fallida']], 400);
            }
        }

        // Guardar
        $repo = new EstudianteRepository();
        $pdo  = $repo->getPdo(); // si ya tienes un contenedor, adáptalo
        $pdo->beginTransaction();
        try {
            $respId = $repo->upsertResponsable($data['responsable']); // (usa tu método con el fix)
            $idEst  = $repo->createEstudiante($data['estudiante'], $respId);
            $pdo->commit();
            return $this->json($res, ['ok' => true, 'id' => $idEst], 201);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            // Log real aquí
            return $this->json($res, ['ok' => false, 'errors' => ['db' => 'No se pudo guardar']], 500);
        }
    }

    private function json($res, array $payload, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        return $res;
    }
}
