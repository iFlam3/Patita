<?php
/**
 * Layout principal.
 *
 * @var string      $content
 * @var string|null $title
 * @var string|null $description
 */
$appName = (string) config('app.name');
$pageTitle = isset($title) && $title !== null && $title !== '' ? $title . ' | ' . $appName : $appName . ': accesorios e insumos para mascotas';
$metaDescription = $description ?? 'Accesorios, alimento e insumos para perros, gatos, aves, peces, reptiles, caballos y animales pequeños.';
?>
<!doctype html>
<html lang="es" data-locale="<?= e(config('app.locale')) ?>" data-currency="<?= e(config('app.currency')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="theme-color" content="#218739">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" href="<?= e(asset('assets/images/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..900&amp;display=swap">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <?php if (config('app.demo_notice') === true): ?>
        <div class="demo-strip">
            <p class="container">Tienda de demostración: productos, marcas, precios y existencias son datos de prueba. No se realizan cobros.</p>
        </div>
    <?php endif; ?>

    <?= App\Core\View::partial('partials/header') ?>

    <main id="contenido" tabindex="-1">
        <?= App\Core\View::partial('partials/flash') ?>
        <?= $content ?>
    </main>

    <?= App\Core\View::partial('partials/footer') ?>

    <div class="toast-region" data-toast-region aria-live="polite" aria-atomic="true"></div>
</body>
</html>
