<?php
// src/ResponseHelper.php

declare(strict_types=1);

namespace CECNSR;

use Psr\Http\Message\ResponseInterface as Response;

class ResponseHelper
{
    public static function json(Response $response, array $payload, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
