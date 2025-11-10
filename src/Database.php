<?php

declare(strict_types=1);

namespace CECNSR;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    \DB_DSN,
                    \DB_USER,
                    \DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \RuntimeException("DB connection failed");
            }
        }
        return self::$pdo;
    }
}
