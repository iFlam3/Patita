<?php

declare(strict_types=1);

/*
 * Front controller. El document root del servidor debe ser public/.
 *
 * Con el servidor embebido de PHP:
 *   php -S 127.0.0.1:8080 -t public public/index.php
 * los archivos estáticos existentes dentro de public/ se sirven directamente.
 */
if (PHP_SAPI === 'cli-server') {
    $requested = rawurldecode((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
    $file = realpath(__DIR__ . $requested);
    if ($file !== false && is_file($file) && $file !== __FILE__
        && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

App\Core\Kernel::handle();
