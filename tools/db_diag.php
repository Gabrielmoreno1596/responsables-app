<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/env.php';

try {
    $pdo = new PDO(\DB_DSN, \DB_USER, \DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "OK DB\n";
    $c = $pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'] ?? 0;
    echo "users.count = $c\n";
    $row = $pdo->query("SELECT id, username, password_hash, role FROM users WHERE username='admin' LIMIT 1")->fetch();
    echo "admin.row = " . json_encode($row) . "\n";
    if ($row) {
        echo "password_verify(admin123) = " . (password_verify('admin123', $row['password_hash']) ? 'true' : 'false') . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
