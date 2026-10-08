<?php
/**
 * Encabezado común de páginas informativas.
 *
 * @var string      $heading
 * @var string|null $intro
 * @var bool        $updated Muestra la fecha de última actualización.
 */
?>
<?= App\Core\View::partial('partials/breadcrumbs', ['items' => [[$heading, null]]]) ?>
<header class="page-head">
    <div>
        <h1><?= e($heading) ?></h1>
        <?php if (($intro ?? null) !== null): ?>
            <p class="page-intro"><?= e($intro) ?></p>
        <?php endif; ?>
        <?php if (($updated ?? false) === true): ?>
            <p class="field-hint">Última actualización: 8 de octubre de 2026.</p>
        <?php endif; ?>
    </div>
</header>
