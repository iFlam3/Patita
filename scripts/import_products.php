<?php

declare(strict_types=1);

/*
 * Importador de productos (solo CLI; nunca se ejecuta durante la navegación).
 *
 * Alternativa recomendada, archivo JSON o CSV:
 *   php scripts/import_products.php --file=database/import-samples/productos-ejemplo.csv [--dry-run]
 *
 * Fuentes web (deshabilitadas por defecto; requieren autorización escrita):
 *   php scripts/import_products.php --list-sources
 *   php scripts/import_products.php --source=petstore_direct --check-robots --url=URL
 *   php scripts/import_products.php --source=petstore_direct --url=URL [--url=URL2] [--pet=dog] [--category=toys] [--dry-run]
 *   php scripts/import_products.php --source=petstore_direct --urls-file=urls.txt [--limit=10] [--dry-run]
 *
 * urls.txt: una URL por línea, opcionalmente "url,pet_type,category".
 * Líneas vacías o que empiezan con # se ignoran.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Import\BlockedException;
use App\Services\Import\FileImportReader;
use App\Services\Import\HttpClient;
use App\Services\Import\ImportException;
use App\Services\Import\ImportReport;
use App\Services\Import\ProductImporter;
use App\Services\Import\RobotsGuard;
use App\Services\Import\Sources;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$options = getopt('', ['file:', 'source:', 'url:', 'urls-file:', 'pet:', 'category:', 'limit:', 'dry-run', 'check-robots', 'list-sources', 'help']);
$dryRun = isset($options['dry-run']);

$say = static function (string $line): void {
    echo $line . PHP_EOL;
};

if (isset($options['help']) || $options === []) {
    $say('Importador de productos de Patita');
    $say('  --file=ruta.json|ruta.csv     Importa desde archivo (alternativa al scraping).');
    $say('  --list-sources                Muestra fuentes web, verificación y condiciones.');
    $say('  --source=clave                Fuente web (requiere IMPORT_ALLOWED_SOURCES).');
    $say('  --url=URL                     URL de ficha de producto (se puede repetir).');
    $say('  --urls-file=archivo.txt       Una URL por línea ("url,pet_type,category" opcional).');
    $say('  --pet=dog --category=toys     Clasificación para todas las URL (si no, se infiere).');
    $say('  --limit=N                     Máximo de URL a procesar (por defecto 20).');
    $say('  --check-robots                Solo verifica robots.txt, no descarga fichas.');
    $say('  --dry-run                     No escribe en la base de datos.');
    exit(0);
}

// ------------------------------------------------------------ listado de fuentes

if (isset($options['list-sources'])) {
    $allowed = (array) config('app.import.allowed_sources', []);
    foreach (Sources::all() as $key => $source) {
        $check = $source->verification();
        $say(sprintf('%s (%s) — %s', $source->name(), $key, $source->host()));
        $say('  Habilitada: ' . (in_array($key, $allowed, true) ? 'sí (IMPORT_ALLOWED_SOURCES)' : 'no'));
        $say('  Verificación: ' . ($check['status'] === 'verified' ? 'verificado el ' . $check['checked_at'] : 'NO verificado'));
        $say('  ' . wordwrap($check['note'], 100, "\n  "));
        $say('  Condiciones: ' . wordwrap($source->termsNote(), 100, "\n  "));
        $say('');
    }
    exit(0);
}

// ------------------------------------------------------------ archivo JSON/CSV

if (isset($options['file'])) {
    $path = (string) $options['file'];
    if (!is_file($path) && is_file(BASE_PATH . '/' . ltrim($path, '/\\'))) {
        $path = BASE_PATH . '/' . ltrim($path, '/\\');
    }
    $report = new ImportReport('archivo', basename($path), $dryRun);
    $importer = new ProductImporter($dryRun);

    try {
        $rows = FileImportReader::read($path);
    } catch (ImportException $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    $say(sprintf('Leídas %d fila(s) de %s%s.', count($rows), basename($path), $dryRun ? ' (simulación)' : ''));
    foreach ($rows as $index => $row) {
        $sku = (string) ($row['sku'] ?? '');
        // En CSV la fila 1 es el encabezado; en JSON se cuenta desde 1.
        $position = str_ends_with(strtolower($path), '.csv') ? 'fila ' . ($index + 2) : 'registro ' . ($index + 1);
        $ref = $position . ($sku !== '' ? ' (' . $sku . ')' : '');
        try {
            $result = $importer->importFileRow($row);
        } catch (Throwable $e) {
            $result = ['action' => 'error', 'message' => 'Error inesperado: ' . $e->getMessage()];
        }
        $report->add($result['action'], $ref, $result['message']);
        $say(sprintf('  [%s] %s: %s', $result['action'], $ref, $result['message']));
    }

    $file = $report->save();
    $say($report->summaryLine());
    $say('Reporte: storage/imports/' . basename($file));
    exit($report->counts()['error'] > 0 ? 2 : 0);
}

// ------------------------------------------------------------ fuente web

if (!isset($options['source'])) {
    fwrite(STDERR, "Indica --file o --source. Usa --help para ver opciones.\n");
    exit(1);
}

$source = Sources::get((string) $options['source']);
if ($source === null) {
    fwrite(STDERR, 'Fuente desconocida. Disponibles: ' . implode(', ', array_keys(Sources::all())) . PHP_EOL);
    exit(1);
}

// URLs desde --url y --urls-file.
$targets = [];
foreach ((array) ($options['url'] ?? []) as $url) {
    $targets[] = ['url' => trim((string) $url), 'pet' => null, 'category' => null];
}
if (isset($options['urls-file'])) {
    $listFile = (string) $options['urls-file'];
    if (!is_file($listFile)) {
        fwrite(STDERR, "No se encontró el archivo de URL: $listFile\n");
        exit(1);
    }
    foreach (file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = array_map('trim', explode(',', $line));
        $targets[] = ['url' => $parts[0], 'pet' => $parts[1] ?? null, 'category' => $parts[2] ?? null];
    }
}
if ($targets === []) {
    fwrite(STDERR, "Indica al menos una --url o un --urls-file.\n");
    exit(1);
}

$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : 20;
$targets = array_slice($targets, 0, $limit);

$defaultPet = isset($options['pet']) ? (string) $options['pet'] : null;
$defaultCategory = isset($options['category']) ? (string) $options['category'] : null;
if ($defaultPet !== null && !isset(pets()[$defaultPet])) {
    fwrite(STDERR, '--pet debe ser uno de: ' . implode(', ', array_keys(pets())) . PHP_EOL);
    exit(1);
}
if ($defaultCategory !== null && !isset(categories()[$defaultCategory])) {
    fwrite(STDERR, '--category debe ser uno de: ' . implode(', ', array_keys(categories())) . PHP_EOL);
    exit(1);
}

$http = HttpClient::fromConfig();
$robots = new RobotsGuard($http);

// Solo verificación de robots.txt (no descarga fichas ni requiere habilitar la fuente).
if (isset($options['check-robots'])) {
    foreach ($targets as $target) {
        if (!$source->supports($target['url'])) {
            $say('[no válida] ' . $target['url'] . ' no es una ficha de ' . $source->host());
            continue;
        }
        try {
            $check = $robots->check($target['url']);
            $say(($check['allowed'] ? '[permitida] ' : '[bloqueada] ') . $target['url'] . ' — ' . $check['reason']);
        } catch (ImportException $e) {
            $say('[error] ' . $target['url'] . ' — ' . $e->getMessage());
        }
    }
    $say('Recuerda: que robots.txt lo permita no otorga derechos de reutilización. ' . $source->termsNote());
    exit(0);
}

if (!Sources::isAllowed($source->key())) {
    fwrite(STDERR, "La fuente '{$source->key()}' no está habilitada.\n");
    fwrite(STDERR, wordwrap($source->termsNote(), 100) . "\n");
    fwrite(STDERR, "Solo si cuentas con autorización escrita, agrega IMPORT_ALLOWED_SOURCES={$source->key()} en .env.\n");
    exit(3);
}

$verification = $source->verification();
if ($verification['status'] !== 'verified') {
    $say('ADVERTENCIA: este adaptador NO está verificado. ' . $verification['note']);
}

$report = new ImportReport('web', $source->key(), $dryRun);
$importer = new ProductImporter($dryRun);
$say(sprintf('Procesando %d URL de %s%s. Pausa mínima entre solicitudes: %d s.', count($targets), $source->name(), $dryRun ? ' (simulación)' : '', (int) config('app.import.delay_seconds')));

foreach ($targets as $target) {
    $url = $target['url'];
    try {
        if (!$source->supports($url)) {
            throw new ImportException('La URL no corresponde a una ficha de producto de ' . $source->host() . '.');
        }

        $check = $robots->check($url);
        if (!$check['allowed']) {
            $report->add('skipped', $url, $check['reason']);
            $say('  [omitida] ' . $url . ' — ' . $check['reason']);
            continue;
        }

        $response = $http->get($url);
        if ($response['status'] !== 200) {
            throw new ImportException('La fuente respondió HTTP ' . $response['status'] . '.');
        }

        $data = $source->extract($response['body'], $url);
        $pet = $target['pet'] !== null && $target['pet'] !== '' ? $target['pet'] : $defaultPet;
        $category = $target['category'] !== null && $target['category'] !== '' ? $target['category'] : $defaultCategory;
        if (($pet !== null && !isset(pets()[$pet])) || ($category !== null && !isset(categories()[$category]))) {
            throw new ImportException('pet_type o category no válidos en el archivo de URL.');
        }

        $result = $importer->importScraped($source, $data, $url, $pet, $category);
        $report->add($result['action'], $url, $result['message'] . ($response['from_cache'] ? ' (desde caché)' : ''));
        $say(sprintf('  [%s] %s — %s', $result['action'], $url, $result['message']));
    } catch (BlockedException $e) {
        $report->add('error', $url, $e->getMessage());
        $report->stop($e->getMessage());
        $say('  [detenido] ' . $e->getMessage());
        break;
    } catch (ImportException $e) {
        $report->add('error', $url, $e->getMessage());
        $say('  [error] ' . $url . ' — ' . $e->getMessage());
    }
}

$file = $report->save();
$say($report->summaryLine());
$say('Reporte: storage/imports/' . basename($file));
exit($report->counts()['error'] > 0 ? 2 : 0);
