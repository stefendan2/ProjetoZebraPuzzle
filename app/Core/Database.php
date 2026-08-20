<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Responsável por criar e reutilizar a conexão PDO da aplicação.
 *
 * A configuração é lida de app/config/database.php e a conexão é criada
 * com charset utf8mb4 e modo de erro por exceção.
 */
final class Database
{
    /**
     * Instância única de PDO para reaproveitamento durante a requisição.
     */
    private static ?PDO $connection = null;

    /**
     * Retorna a conexão PDO da aplicação.
     *
     * @throws PDOException quando a conexão falha.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $configPath = dirname(__DIR__) . '/config/database.php';
        $config = require $configPath;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            (int) $config['port'],
            $config['dbname'],
            $config['charset']
        );

        self::$connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$connection;
    }
}
