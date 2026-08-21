<?php

namespace core\database;

use PDO;
use PDOException;

/**
 * Conexão PDO nova e isolada para a arquitetura do roadmap.
 *
 * Esta classe lê as credenciais em app/config/database.php e cria a conexão
 * com utf8mb4, exceções ativadas e prepares reais.
 * O DBConnection legado permanece intacto por enquanto.
 */
final class PdoConnection
{
    /**
     * Guarda a instância única de PDO durante a requisição.
     */
    private static ?PDO $connection = null;

    /**
     * Retorna a conexão PDO compartilhada.
     *
     * @throws PDOException quando a conexão não puder ser criada.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $configPath = dirname(__DIR__, 2) . '/config/database.php';
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
