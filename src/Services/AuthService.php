<?php

declare(strict_types=1);

namespace CECNSR\Services;

use CECNSR\Database;
use PDO;

class AuthService
{
    public function attempt(string $username, string $password): ?array
    {
        $username = trim($username);
        $pdo = Database::pdo();

        $st = $pdo->prepare(
            "SELECT id, username, password_hash, role
       FROM users
       WHERE username = :u
       LIMIT 1"
        );
        $st->execute([':u' => $username]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;

        return $user;
    }
}
