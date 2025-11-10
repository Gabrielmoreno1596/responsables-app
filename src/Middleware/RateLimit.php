<?php

namespace CECNSR\Middleware;


class RateLimit
{
    private int $max;
    private int $window;


    public function __construct(int $max = 20, int $windowSeconds = 3600)
    {
        $this->max = $max;
        $this->window = $windowSeconds;
    }


    public function __invoke($request, $handler)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $bucket = sys_get_temp_dir() . '/rate_' . md5($ip);
        $now = time();
        $hits = [];
        if (file_exists($bucket)) {
            $json = file_get_contents($bucket);
            $hits = json_decode($json, true) ?: [];
        }
        $hits = array_values(array_filter($hits, fn($t) => ($now - (int)$t) < $this->window));
        $hits[] = $now;
        file_put_contents($bucket, json_encode($hits), LOCK_EX);


        if (count($hits) > $this->max) {
            $response = new \Slim\Psr7\Response(429);
            $response->getBody()->write(json_encode(['error' => 'rate_limit']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string)$this->window);
        }
        return $handler->handle($request);
    }
}
