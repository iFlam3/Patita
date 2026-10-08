<?php

declare(strict_types=1);

/*
 * Arma el paquete de despliegue para InfinityFree (u otro hosting compartido
 * donde todo debe ir dentro del document root "htdocs").
 *
 * Uso:
 *   php scripts/build_infinityfree.php --url=https://tusitio.infinityfreeapp.com \
 *       --db-host=sqlXXX.infinityfree.com --db-name=if0_XXX_patita \
 *       --db-user=if0_XXX --db-pass=CLAVE
 *
 * Todos los parámetros son opcionales: lo que falte queda como texto
 * "CAMBIAR_..." en htdocs/_app/.env para que lo completes después.
 *
 * Genera dist/infinityfree/:
 *   htdocs/            contenido para subir a htdocs (público + _app protegido)
 *   patita-htdocs.zip  lo mismo comprimido
 *   database.sql       esquema + datos demo para importar en phpMyAdmin
 *
 * ATENCIÓN: htdocs/_app/.env incluye la configuración de correo de tu .env
 * local (contraseña SMTP). No compartas el ZIP ni lo subas a Git (dist/ está
 * en .gitignore).
 */

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$options = getopt('', ['url:', 'db-host:', 'db-name:', 'db-user:', 'db-pass:']);
$dist = BASE_PATH . '/dist/infinityfree';
$htdocs = $dist . '/htdocs';
$appDir = $htdocs . '/_app';

/** Borra un directorio recursivamente (solo dentro de dist/). */
$remove = static function (string $dir) use (&$remove): void {
    if (!is_dir($dir) || !str_starts_with(realpath($dir) ?: '', realpath(BASE_PATH) . DIRECTORY_SEPARATOR . 'dist')) {
        return;
    }
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        is_dir($path) && !is_link($path) ? $remove($path) : unlink($path);
    }
    rmdir($dir);
};

/** Copia un directorio recursivamente; $skip recibe la ruta relativa. */
$copy = static function (string $from, string $to, ?callable $skip = null, string $rel = '') use (&$copy): int {
    if (!is_dir($to) && !mkdir($to, 0775, true) && !is_dir($to)) {
        throw new RuntimeException('No se pudo crear ' . $to);
    }
    $count = 0;
    foreach (scandir($from) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $relative = ltrim($rel . '/' . $item, '/');
        if ($skip !== null && $skip($relative)) {
            continue;
        }
        $src = $from . '/' . $item;
        if (is_dir($src)) {
            $count += $copy($src, $to . '/' . $item, $skip, $relative);
        } else {
            copy($src, $to . '/' . $item);
            $count++;
        }
    }

    return $count;
};

$remove($dist);
mkdir($appDir, 0775, true);

// 1. Archivos públicos (sin las imágenes subidas en local).
$files = $copy(BASE_PATH . '/public', $htdocs, static fn (string $r): bool =>
    str_starts_with($r, 'uploads/products/') && $r !== 'uploads/products/.gitkeep');

// 2. Código de la aplicación dentro de _app (bloqueado por .htaccess).
$files += $copy(BASE_PATH . '/app', $appDir . '/app');

// 3. storage vacío con sus protecciones.
foreach (['cache/http', 'logs', 'mail', 'ratelimit', 'imports'] as $sub) {
    mkdir($appDir . '/storage/' . $sub, 0775, true);
    file_put_contents($appDir . '/storage/' . $sub . '/.gitkeep', '');
}
copy(BASE_PATH . '/storage/.htaccess', $appDir . '/storage/.htaccess');

// 4. Reglas de acceso.
$denyAll = <<<'HTACCESS'
# Código, configuración y datos privados: nunca se sirven por web.
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>

HTACCESS;
file_put_contents($appDir . '/.htaccess', $denyAll);

file_put_contents($htdocs . '/.htaccess', <<<'HTACCESS'
# Patita en InfinityFree: htdocs es el document root.
Options -Indexes
DirectoryIndex index.php

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Para forzar HTTPS cuando el certificado SSL esté activo, quita el "#"
    # de las dos líneas siguientes.
    # RewriteCond %{HTTP:X-Forwarded-Proto} !https
    # RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Bloquea el código de la aplicación (_app) y archivos ocultos.
    RewriteRule ^_app(/|$) - [F,L]
    RewriteRule (^|/)\. - [F,L]

    # Archivos y carpetas reales se sirven tal cual.
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Todo lo demás pasa por el front controller.
    RewriteRule ^ index.php [L]
</IfModule>

<IfModule mod_headers.c>
    <FilesMatch "\.(css|js|svg|png|jpe?g|webp|woff2?)$">
        Header set Cache-Control "public, max-age=604800"
    </FilesMatch>
    Header always set X-Content-Type-Options "nosniff"
</IfModule>

AddType image/svg+xml .svg

HTACCESS);

// 5. .env de producción a partir de .env.example y del .env local.
$local = [];
foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $m) === 1) {
        $local[$m[1]] = $m[2];
    }
}
$keepFromLocal = '/^(APP_NAME|APP_COUNTRY|APP_LOCALE|APP_CURRENCY|APP_TIMEZONE|SHIPPING_.*|PAYMENT_METHODS|BANK_TRANSFER_DETAILS|MAX_QTY_PER_ITEM|MAIL_.*|SMTP_.*|CONTACT_.*)$/';
$quote = static fn (string $v): string => preg_match('/[\s#"]/', $v) === 1 ? '"' . str_replace('"', '\"', $v) . '"' : $v;
$overrides = [
    'APP_ENV'               => 'production',
    'APP_DEBUG'             => 'false',
    'APP_URL'               => rtrim((string) ($options['url'] ?? 'https://CAMBIAR_SUBDOMINIO.infinityfreeapp.com'), '/'),
    'APP_DEMO_NOTICE'       => 'false',
    'DB_HOST'               => $quote((string) ($options['db-host'] ?? 'CAMBIAR_sqlXXX.infinityfree.com')),
    'DB_PORT'               => '3306',
    'DB_DATABASE'           => $quote((string) ($options['db-name'] ?? 'CAMBIAR_if0_XXXXXXXX_patita')),
    'DB_USERNAME'           => $quote((string) ($options['db-user'] ?? 'CAMBIAR_if0_XXXXXXXX')),
    'DB_PASSWORD'           => $quote((string) ($options['db-pass'] ?? 'CAMBIAR_CLAVE_DE_LA_BASE')),
    'SESSION_SECURE_COOKIE' => 'auto',
];
$env = [];
foreach (file(BASE_PATH . '/.env.example', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    if (preg_match('/^([A-Z][A-Z0-9_]*)=/', $line, $m) === 1) {
        $key = $m[1];
        if (array_key_exists($key, $overrides)) {
            $line = $key . '=' . $overrides[$key];
        } elseif (preg_match($keepFromLocal, $key) === 1 && array_key_exists($key, $local)) {
            $line = $key . '=' . $local[$key];
        }
    }
    $env[] = $line;
}
file_put_contents($appDir . '/.env', implode("\n", $env) . "\n");

// 6. SQL para phpMyAdmin.
$sql = "-- Patita: esquema y datos iniciales para importar en phpMyAdmin.\n"
    . "-- Selecciona primero tu base de datos (if0_..._patita) y luego usa Importar.\n\n"
    . file_get_contents(BASE_PATH . '/database/schema.sql') . "\n"
    . file_get_contents(BASE_PATH . '/database/seed.sql');
file_put_contents($dist . '/database.sql', $sql);

// 7. ZIP del contenido de htdocs.
$zipFile = $dist . '/patita-htdocs.zip';
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('No se pudo crear el ZIP.');
}
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($htdocs, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    $path = str_replace('\\', '/', (string) $file->getPathname());
    $zip->addFile($path, substr($path, strlen(str_replace('\\', '/', $htdocs)) + 1));
}
$zip->close();

$pending = array_keys(array_filter($overrides, static fn (string $v): bool => str_contains($v, 'CAMBIAR')));

echo "Paquete listo en dist/infinityfree/\n";
echo "  htdocs/               $files archivos\n";
echo "  patita-htdocs.zip     " . round(filesize($zipFile) / 1024) . " KB\n";
echo "  database.sql          para importar en phpMyAdmin\n";
echo $pending === []
    ? "  htdocs/_app/.env      completo\n"
    : "  htdocs/_app/.env      FALTA completar: " . implode(', ', $pending) . "\n";
echo "\nNo compartas el ZIP: incluye la configuración de correo (contraseña SMTP).\n";
