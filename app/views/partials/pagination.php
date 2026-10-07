<?php
/**
 * @var int                   $page
 * @var int                   $pages
 * @var string                $base  Ruta de la página (sin consulta).
 * @var array<string, string> $query Parámetros a conservar.
 */
if ($pages <= 1) {
    return;
}
$link = static fn (int $n): string => url($base, array_merge($query, ['pagina' => $n > 1 ? (string) $n : '']));

// Ventana de páginas: primera, última y dos alrededor de la actual.
$numbers = [];
for ($i = 1; $i <= $pages; $i++) {
    if ($i === 1 || $i === $pages || abs($i - $page) <= 2) {
        $numbers[] = $i;
    } elseif (end($numbers) !== null) {
        $numbers[] = null;
    }
}
?>
<nav class="pagination" aria-label="Paginación">
    <?php if ($page > 1): ?>
        <a class="pagination-step" href="<?= e($link($page - 1)) ?>" rel="prev" data-page-link><?= icon('chevron-left', 'icon icon-sm') ?> Anterior</a>
    <?php endif; ?>
    <ol>
        <?php foreach ($numbers as $n): ?>
            <?php if ($n === null): ?>
                <li class="pagination-gap" aria-hidden="true">…</li>
            <?php elseif ($n === $page): ?>
                <li><a href="<?= e($link($n)) ?>" aria-current="page" data-page-link><span class="sr-only">Página </span><?= $n ?></a></li>
            <?php else: ?>
                <li><a href="<?= e($link($n)) ?>" data-page-link><span class="sr-only">Página </span><?= $n ?></a></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
    <?php if ($page < $pages): ?>
        <a class="pagination-step" href="<?= e($link($page + 1)) ?>" rel="next" data-page-link>Siguiente <?= icon('chevron-right', 'icon icon-sm') ?></a>
    <?php endif; ?>
</nav>
