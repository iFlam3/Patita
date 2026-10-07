<?php
/**
 * @var array<string, App\Services\Import\SourceAdapter> $sources
 * @var list<string>                                      $allowedSources
 * @var list<array<string, mixed>>                        $reports
 * @var array<string, mixed>                              $importConfig
 */
$actionLabels = ['created' => 'Creado', 'updated' => 'Actualizado', 'skipped' => 'Omitido', 'error' => 'Error'];
?>
<div class="container page">
    <header class="page-head">
        <div>
            <h1>Importador de productos</h1>
            <p class="page-intro">El importador se ejecuta solo por línea de comandos, nunca durante la navegación. Aquí ves sus instrucciones y los resultados guardados.</p>
        </div>
    </header>
    <?= App\Core\View::partial('partials/admin-nav', ['active' => 'importador']) ?>

    <section class="panel" aria-labelledby="imp-archivo">
        <h2 id="imp-archivo">Importar desde JSON o CSV (recomendado)</h2>
        <p>Usa esta vía para cargar datos propios o de proveedores que te los entreguen con permiso. Los productos se identifican por SKU: si ya existe, se actualiza.</p>
        <pre class="code"><code>php scripts/import_products.php --file=database/import-samples/productos-ejemplo.csv --dry-run
php scripts/import_products.php --file=database/import-samples/productos-ejemplo.json</code></pre>
        <p class="field-hint">--dry-run valida y muestra el resultado sin escribir en la base de datos. Los productos con moneda distinta de <?= e(config('app.currency')) ?> o precio 0 quedan inactivos.</p>
    </section>

    <section class="panel" aria-labelledby="imp-fuentes">
        <h2 id="imp-fuentes">Fuentes web</h2>
        <p>Antes de extraer, el importador consulta robots.txt, identifica su cliente con un User-Agent descriptivo, espera <?= (int) $importConfig['delay_seconds'] ?> s entre solicitudes, usa caché y reintenta como máximo <?= (int) $importConfig['max_retries'] ?> vez/veces. Si detecta un CAPTCHA o bloqueo, se detiene. Los productos extraídos entran inactivos, con precio 0 y stock 0, hasta que definas precio local, stock, texto e imagen propios.</p>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th scope="col">Fuente</th><th scope="col">Verificación</th><th scope="col">Condiciones de uso</th><th scope="col">Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $key => $source): ?>
                        <?php $check = $source->verification(); $enabled = in_array($key, $allowedSources, true); ?>
                        <tr>
                            <th scope="row"><?= e($source->name()) ?><span class="cell-sub"><?= e($key) ?>, <?= e($source->host()) ?></span></th>
                            <td>
                                <span class="tag <?= $check['status'] === 'verified' ? 'tag-manual' : 'tag-warn' ?>"><?= $check['status'] === 'verified' ? 'Verificado ' . e((string) $check['checked_at']) : 'No verificado' ?></span>
                                <span class="cell-sub"><?= e($check['note']) ?></span>
                            </td>
                            <td><span class="cell-sub"><?= e($source->termsNote()) ?></span></td>
                            <td><span class="tag <?= $enabled ? 'tag-warn' : 'tag-demo' ?>"><?= $enabled ? 'Habilitada en .env' : 'Deshabilitada' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <pre class="code"><code>php scripts/import_products.php --list-sources
php scripts/import_products.php --source=petstore_direct --check-robots --url=https://www.petstore.direct/products/ejemplo
php scripts/import_products.php --source=petstore_direct --urls-file=urls.txt --limit=10 --dry-run</code></pre>
        <p class="field-hint">Para habilitar una fuente agrega su clave a IMPORT_ALLOWED_SOURCES en .env únicamente si tienes autorización escrita del sitio. Ninguna fuente viene habilitada.</p>
    </section>

    <section class="panel" aria-labelledby="imp-reportes">
        <h2 id="imp-reportes">Últimas ejecuciones</h2>
        <?php if ($reports === []): ?>
            <p>Todavía no hay reportes en storage/imports.</p>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <?php $c = (array) ($report['counts'] ?? []); ?>
                <details class="report">
                    <summary>
                        <strong><?= e(format_datetime((string) ($report['finished_at'] ?? ''))) ?></strong>
                        <?= e((string) ($report['mode'] ?? '')) ?> <?= e((string) ($report['source'] ?? '')) ?><?= !empty($report['dry_run']) ? ' (simulación)' : '' ?>:
                        <?= (int) ($c['created'] ?? 0) ?> creados, <?= (int) ($c['updated'] ?? 0) ?> actualizados, <?= (int) ($c['skipped'] ?? 0) ?> omitidos, <?= (int) ($c['error'] ?? 0) ?> con error
                    </summary>
                    <?php if (!empty($report['stopped'])): ?>
                        <p class="inline-notice inline-notice-warn"><?= icon('circle-alert', 'icon icon-sm') ?> Detenido: <?= e((string) $report['stopped']) ?></p>
                    <?php endif; ?>
                    <ul class="report-entries">
                        <?php foreach (array_slice((array) ($report['entries'] ?? []), 0, 200) as $entry): ?>
                            <li class="report-<?= e((string) ($entry['action'] ?? '')) ?>">
                                <span class="tag"><?= e($actionLabels[$entry['action'] ?? ''] ?? (string) ($entry['action'] ?? '')) ?></span>
                                <span class="break-all"><?= e((string) ($entry['ref'] ?? '')) ?></span>: <?= e((string) ($entry['message'] ?? '')) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="field-hint">Archivo: storage/imports/<?= e((string) $report['file']) ?></p>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
