<?php

declare(strict_types=1);

/*
 * Arranque compartido por la web (public/index.php) y los scripts CLI.
 */

if (PHP_VERSION_ID < 80000) {
    fwrite(STDERR, "Patita requiere PHP 8.2 o superior.\n");
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
// Carpeta pública (document root). Por defecto public/; en hostings donde todo
// vive dentro del document root (InfinityFree) la define public/index.php.
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    // App\Services\Import\HttpClient -> app/services/import/HttpClient.php
    $parts = explode('\\', substr($class, 4));
    $file = array_pop($parts);
    $dir = implode('/', array_map('strtolower', $parts));
    $path = BASE_PATH . '/app/' . ($dir !== '' ? $dir . '/' : '') . $file . '.php';
    if (is_file($path)) {
        require $path;
    }
});

// Dependencias de Composer, si en el futuro se agregan (hoy no hay ninguna).
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

require BASE_PATH . '/app/helpers.php';

App\Core\Env::load(BASE_PATH . '/.env');

// Las fechas se guardan en UTC; se convierten a APP_TIMEZONE al mostrarlas.
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') === true && PHP_SAPI === 'cli' ? '1' : '0');
ini_set('log_errors', '1');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
