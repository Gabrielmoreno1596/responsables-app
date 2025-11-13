<?php
// src/Services/AuthService.php
declare(strict_types=1);

namespace CECNSR\Services;

use CECNSR\Database;
use PDO;

final class AuthService
{
    public function attempt(string $username, string $password): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if (!$row) return null;
        if (!password_verify($password, $row['password_hash'])) return null;
        return $row;
    }
}
