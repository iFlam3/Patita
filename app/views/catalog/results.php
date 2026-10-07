<?php
/**
 * Resultados del catálogo. Se renderiza dentro de la página o sola
 * (?parcial=1) cuando el JavaScript actualiza los resultados sin recargar.
 *
 * @var array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int} $result
 * @var array<string, string>                   $query
 * @var string                                  $base
 * @var list<array{label: string, url: string}> $chips
 * @var list<string>                            $notices
 * @var array<string, mixed>                    $filters
 */
$total = $result['total'];
$from = $total === 0 ? 0 : ($result['page'] - 1) * $result['per_page'] + 1;
$to = min($total, $result['page'] * $result['per_page']);
$returnTo = $base . ($query !== [] ? '?' . http_build_query($query) : '');
?>
<div class="results-toolbar">
    <p class="results-count" data-results-count>
        <?php if ($total === 0): ?>
            Sin resultados
        <?php else: ?>
            <strong><?= $total ?></strong> <?= $total === 1 ? 'producto' : 'productos' ?>
            <?php if ($result['pages'] > 1): ?><span class="results-range">(<?= $from ?> a <?= $to ?>)</span><?php endif; ?>
        <?php endif; ?>
    </p>
    <div class="results-sort">
        <label for="f-orden">Ordenar por</label>
        <select id="f-orden" name="orden" form="catalog-filters" data-autosubmit>
            <?php foreach (App\Models\Product::SORT_LABELS as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= $filters['sort'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php foreach ($notices as $notice): ?>
    <p class="inline-notice"><?= icon('info', 'icon icon-sm') ?> <?= e($notice) ?></p>
<?php endforeach; ?>

<?php if ($chips !== []): ?>
    <ul class="chips" aria-label="Filtros activos">
        <?php foreach ($chips as $chip): ?>
            <li>
                <a class="chip" href="<?= e($chip['url']) ?>" data-page-link>
                    <?= e($chip['label']) ?> <?= icon('x', 'icon icon-sm') ?><span class="sr-only"> (quitar filtro)</span>
                </a>
            </li>
        <?php endforeach; ?>
        <?php if (count($chips) > 1): ?>
            <li><a class="chip-clear" href="<?= e(url($base)) ?>" data-page-link>Quitar todos</a></li>
        <?php endif; ?>
    </ul>
<?php endif; ?>

<?php if ($total === 0): ?>
    <div class="state state-empty">
        <?= icon('search', 'icon icon-xl') ?>
        <h2>No encontramos productos con esos filtros</h2>
        <p>
            <?php if ($filters['q'] !== ''): ?>
                Revisa cómo escribiste "<?= e($filters['q']) ?>" o prueba con otra palabra.
            <?php endif; ?>
            Quita algún filtro para ver más opciones.
        </p>
        <a class="btn btn-primary" href="<?= e(url($base)) ?>" data-page-link>Ver todos los productos</a>
    </div>
<?php else: ?>
    <div class="product-grid product-grid-catalog">
        <?php foreach ($result['items'] as $product): ?>
            <?= App\Core\View::partial('partials/product-card', ['product' => $product, 'returnTo' => $returnTo, 'headingLevel' => 'h2']) ?>
        <?php endforeach; ?>
    </div>
    <?= App\Core\View::partial('partials/pagination', [
        'page'  => $result['page'],
        'pages' => $result['pages'],
        'base'  => $base,
        'query' => $query,
    ]) ?>
<?php endif; ?>
