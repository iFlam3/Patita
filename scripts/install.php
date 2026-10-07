<?php

declare(strict_types=1);

/*
 * Instalación de la base de datos: crea la base (si el usuario tiene permiso),
 * ejecuta database/schema.sql y carga database/seed.sql.
 *
 * Uso:
 *   php scripts/install.php              Crea tablas y carga datos demo.
 *   php scripts/install.php --no-seed    Solo crea las tablas.
 *   php scripts/install.php --fresh      Borra las 5 tablas del proyecto y reinstala
 *                                        (pide confirmación; --yes la omite).
 */

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$options = getopt('', ['fresh', 'yes', 'no-seed', 'help']);
if (isset($options['help'])) {
    echo "Uso: php scripts/install.php [--fresh [--yes]] [--no-seed]\n";
    exit(0);
}

$db = (array) config('app.db');
$name = (string) $db['database'];
if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) !== 1) {
    fwrite(STDERR, "DB_DATABASE solo puede tener letras, números y guion bajo.\n");
    exit(1);
}

try {
    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', (string) $db['host'], (int) $db['port']),
        (string) $db['username'],
        (string) $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "No se pudo conectar a MySQL/MariaDB: " . $e->getMessage() . "\nRevisa DB_HOST, DB_PORT, DB_USERNAME y DB_PASSWORD en .env.\n");
    exit(1);
}

try {
    $server->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos `$name` lista.\n";
} catch (PDOException $e) {
    echo "Aviso: no se pudo crear la base (puede que ya exista o falten permisos): " . $e->getMessage() . "\n";
}

$pdo = App\Core\Database::pdo();
$tables = ['order_items', 'orders', 'password_reset_tokens', 'products', 'users'];

if (isset($options['fresh'])) {
    if (!isset($options['yes'])) {
        echo "Se BORRARÁN las tablas " . implode(', ', $tables) . " de `$name` con todos sus datos.\nEscribe \"si\" para continuar: ";
        $answer = strtolower(trim((string) fgets(STDIN)));
        if ($answer !== 'si' && $answer !== 'sí') {
            echo "Cancelado.\n";
            exit(1);
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Tablas eliminadas.\n";
}

$existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$present = array_values(array_intersect($tables, array_map('strval', $existing)));
if ($present !== []) {
    fwrite(STDERR, "Ya existen tablas del proyecto (" . implode(', ', $present) . "). Usa --fresh para reinstalar.\n");
    exit(1);
}

/**
 * Ejecuta un archivo SQL sentencia por sentencia (ignora comentarios "--").
 */
$runFile = static function (PDO $pdo, string $file): int {
    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    $sql = implode("\n", array_filter($lines, static fn (string $line): bool => !str_starts_with(ltrim($line), '--')));
    $count = 0;
    foreach (preg_split('/;\s*(\n|$)/', $sql) ?: [] as $statement) {
        if (trim($statement) === '') {
            continue;
        }
        $pdo->exec($statement);
        $count++;
    }

    return $count;
};

$n = $runFile($pdo, BASE_PATH . '/database/schema.sql');
echo "Esquema aplicado ($n sentencias). Tablas: " . implode(', ', array_reverse($tables)) . ".\n";

if (!isset($options['no-seed'])) {
    $runFile($pdo, BASE_PATH . '/database/seed.sql');
    $total = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    echo "Datos de demostración cargados: $total productos (data_origin = demo, moneda USD).\n";
    if ((string) config('app.currency') !== 'USD') {
        echo "Aviso: APP_CURRENCY es " . config('app.currency') . "; los productos demo están en USD y no se mostrarán hasta ajustarlos.\n";
    }
}

echo "\nSiguiente paso: crea un administrador con\n  php scripts/create_admin.php --email=tu@correo --name=\"Tu nombre\"\n";
