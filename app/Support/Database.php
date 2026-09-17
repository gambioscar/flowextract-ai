<?php

declare(strict_types=1);

namespace FlowExtract\Support;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private ?PDO $connection = null;

    public function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::int('DB_PORT', 3306);
        $database = Env::get('DB_DATABASE', 'flowextract');
        $username = Env::get('DB_USERNAME', 'flowextract');
        $password = Env::get('DB_PASSWORD', '');

        try {
            $this->connection = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Database non disponibile. Verifica la configurazione e riprova.', 0, $exception);
        }

        return $this->connection;
    }
}
