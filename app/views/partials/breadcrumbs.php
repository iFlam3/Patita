<?php
/**
 * @var list<array{0: string, 1: string|null}> $items [etiqueta, ruta o null para la actual]
 */
?>
<nav class="breadcrumbs" aria-label="Ruta de navegación">
    <ol>
        <li><a href="<?= e(url('/')) ?>">Inicio</a></li>
        <?php foreach ($items as [$label, $path]): ?>
            <li>
                <?php if ($path !== null): ?>
                    <a href="<?= e(url($path)) ?>"><?= e($label) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= e($label) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
