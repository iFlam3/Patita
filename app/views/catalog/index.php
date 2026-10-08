<?php
/**
 * Listado compartido: catálogo, página de mascota y página de marca.
 *
 * @var string                $heading
 * @var string                $intro
 * @var string                $base
 * @var array<string, string> $fixed
 * @var string|null           $contextPet
 * @var string|null           $contextBrand
 * @var array<string, int>    $categoryCounts
 * @var array<string, mixed>  $filters
 * @var list<string>          $brandOptions
 * @var list<string>          $categoryOptions
 */
$crumbs = [];
if ($contextPet !== null) {
    $crumbs = [['Mascotas', '/mascotas'], [$heading, null]];
} elseif ($contextBrand !== null) {
    $crumbs = [['Marcas', '/marcas'], [$heading, null]];
} else {
    $crumbs = [['Catálogo', null]];
}
$petData = $contextPet !== null ? pets()[$contextPet] : null;
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => $crumbs]) ?>

    <header class="page-head<?= $petData !== null ? ' page-head-pet' : '' ?>">
        <?php if ($petData !== null): ?>
            <span class="page-head-icon"><?= icon((string) $petData['icon']) ?></span>
        <?php endif; ?>
        <div>
            <h1><?= e($heading) ?></h1>
            <p class="page-intro"><?= e($intro) ?></p>
        </div>
    </header>

    <?php if ($petData !== null): ?>
        <nav class="category-chips" aria-label="Categorías para <?= e($heading) ?>">
            <ul>
                <li><a class="chip-link<?= $filters['category'] === null ? ' is-active' : '' ?>" href="<?= e(url($base)) ?>"<?= $filters['category'] === null ? ' aria-current="true"' : '' ?>>Todo</a></li>
                <?php foreach (categories() as $code => $category): ?>
                    <?php if (($categoryCounts[$code] ?? 0) === 0) { continue; } ?>
                    <?php $isActive = $filters['category'] === $code; ?>
                    <li>
                        <a class="chip-link<?= $isActive ? ' is-active' : '' ?>" href="<?= e(url($base, ['categoria' => $category['slug']])) ?>"<?= $isActive ? ' aria-current="true"' : '' ?>>
                            <?= icon($category['icon'], 'icon icon-sm') ?> <?= e($category['label']) ?>
                            <span class="chip-count"><?= (int) $categoryCounts[$code] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php if (isset($petData['subtypes'])): ?>
            <p class="subtype-links">
                Buscar por especie:
                <?php foreach ($petData['subtypes'] as $term => $label): ?>
                    <a href="<?= e(url($base, ['q' => $term])) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <div class="catalog-layout">
        <aside class="filters" aria-labelledby="filtros-titulo">
            <details class="filters-panel" data-filters-panel>
                <summary id="filtros-titulo"><?= icon('sliders-horizontal', 'icon icon-sm') ?> Filtros</summary>
                <form id="catalog-filters" class="filters-form" action="<?= e(url($base)) ?>" method="get" data-catalog-form>
                    <div class="field">
                        <label for="f-q">Buscar</label>
                        <input id="f-q" type="search" name="q" value="<?= e($filters['q']) ?>" maxlength="100" placeholder="Nombre, marca o descripción">
                    </div>

                    <?php if (!isset($fixed['pet'])): ?>
                        <div class="field">
                            <label for="f-mascota">Mascota</label>
                            <select id="f-mascota" name="mascota" data-autosubmit>
                                <option value="">Todas</option>
                                <?php foreach (pets() as $code => $pet): ?>
                                    <option value="<?= e($pet['slug']) ?>"<?= $filters['pet'] === $code ? ' selected' : '' ?>><?= e($pet['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="f-categoria">Categoría</label>
                        <select id="f-categoria" name="categoria" data-autosubmit>
                            <option value="">Todas</option>
                            <?php foreach ($categoryOptions as $code): ?>
                                <option value="<?= e(category_slug($code)) ?>"<?= $filters['category'] === $code ? ' selected' : '' ?>><?= e(category_label($code)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!isset($fixed['brand'])): ?>
                        <div class="field">
                            <label for="f-marca">Marca</label>
                            <select id="f-marca" name="marca" data-autosubmit>
                                <option value="">Todas</option>
                                <?php foreach ($brandOptions as $brandName): ?>
                                    <option value="<?= e(slugify($brandName)) ?>"<?= $filters['brand'] === $brandName ? ' selected' : '' ?>><?= e($brandName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <fieldset class="field-group">
                        <legend>Precio (<?= e(config('app.currency')) ?>)</legend>
                        <div class="field-row">
                            <div class="field">
                                <label for="f-min">Desde</label>
                                <input id="f-min" type="number" name="precio_min" min="0" step="0.01" inputmode="decimal"
                                       value="<?= $filters['min'] !== null ? e(cents_to_decimal((int) $filters['min'])) : '' ?>">
                            </div>
                            <div class="field">
                                <label for="f-max">Hasta</label>
                                <input id="f-max" type="number" name="precio_max" min="0" step="0.01" inputmode="decimal"
                                       value="<?= $filters['max'] !== null ? e(cents_to_decimal((int) $filters['max'])) : '' ?>">
                            </div>
                        </div>
                    </fieldset>

                    <div class="field-check">
                        <input id="f-stock" type="checkbox" name="disponibles" value="1"<?= $filters['in_stock'] ? ' checked' : '' ?> data-autosubmit>
                        <label for="f-stock">Solo productos con stock</label>
                    </div>

                    <div class="filters-actions">
                        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                        <a class="btn btn-quiet" href="<?= e(url($base)) ?>">Limpiar</a>
                    </div>
                </form>
            </details>
        </aside>

        <section class="catalog-results" data-catalog-results aria-label="Resultados">
            <?php /* $__data contiene todas las variables que recibió esta vista. */ ?>
            <?= App\Core\View::partial('catalog/results', $__data) ?>
        </section>
    </div>

    <?php $guide = $contextPet !== null ? (array) config('catalog.pet_guides.' . $contextPet, []) : []; ?>
    <?php if ($guide !== []): ?>
        <section class="pet-guide" aria-labelledby="guia-titulo">
            <h2 id="guia-titulo">Antes de comprar para <?= e(mb_strtolower($heading, 'UTF-8')) ?></h2>
            <ul class="pet-guide-grid">
                <?php foreach ($guide as $module): ?>
                    <?php $cat = categories()[$module['category']] ?? null; ?>
                    <li class="pet-guide-card">
                        <?php if ($cat !== null): ?>
                            <span class="pet-guide-icon"><?= icon($cat['icon']) ?></span>
                        <?php endif; ?>
                        <h3><?= e($module['title']) ?></h3>
                        <p><?= e($module['text']) ?></p>
                        <?php if ($cat !== null && ($categoryCounts[$module['category']] ?? 0) > 0): ?>
                            <a class="link-more" href="<?= e(url($base, ['categoria' => $cat['slug']])) ?>">Ver <?= e(mb_strtolower($cat['label'], 'UTF-8')) ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="pet-guide-foot">
                <a class="btn btn-quiet" href="<?= e(url('/cuidados/' . pets()[$contextPet]['slug'])) ?>"><?= icon('book-open', 'icon icon-sm') ?> Guía completa de cuidado</a>
                <span class="field-hint">Información general de compra. Para dudas de salud, alimentación o comportamiento, consulta a un veterinario.</span>
            </p>
        </section>
    <?php endif; ?>
</div>
