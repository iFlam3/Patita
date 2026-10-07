<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Conexión PDO única. Siempre con consultas preparadas reales
 * (ATTR_EMULATE_PREPARES = false), excepciones y zona horaria UTC.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = (array) config('app.db');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) $db['host'],
            (int) $db['port'],
            (string) $db['database']
        );

        self::$pdo = new PDO($dsn, (string) $db['username'], (string) $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        self::$pdo->exec("SET time_zone = '+00:00'");
        // Modo estricto aunque el servidor no lo tenga por defecto (p. ej. XAMPP):
        // un dato inválido produce error en lugar de truncarse en silencio.
        self::$pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

        return self::$pdo;
    }

    /**
     * Ejecuta $callback dentro de una transacción. Si lanza una excepción,
     * se revierte y la excepción se propaga.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     */
    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Fecha y hora UTC en formato DATETIME de MySQL.
     */
    public static function now(int $offsetSeconds = 0): string
    {
        return gmdate('Y-m-d H:i:s', time() + $offsetSeconds);
    }
}
