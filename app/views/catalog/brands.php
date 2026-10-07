<?php
/**
 * @var list<array{brand: string, slug: string, total: int, demo_total: int, pets: list<string>}> $brands
 */
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Marcas', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Comprar por marca</h1>
            <p class="page-intro">Marcas con productos disponibles hoy en la tienda. La lista se arma con los productos publicados.</p>
        </div>
    </header>

    <?php if ($brands === []): ?>
        <div class="state state-empty">
            <h2>Todavía no hay marcas publicadas</h2>
            <p>Cuando haya productos activos, sus marcas aparecerán aquí.</p>
            <a class="btn btn-primary" href="<?= e(url('/catalogo')) ?>">Ir al catálogo</a>
        </div>
    <?php else: ?>
        <ul class="brand-grid">
            <?php foreach ($brands as $brand): ?>
                <li class="brand-card">
                    <a href="<?= e(url('/marcas/' . $brand['slug'])) ?>">
                        <span class="brand-card-name"><?= e($brand['brand']) ?></span>
                        <span class="brand-card-count"><?= $brand['total'] === 1 ? '1 producto' : $brand['total'] . ' productos' ?></span>
                        <span class="brand-card-pets">
                            <?php foreach ($brand['pets'] as $pet): ?>
                                <?php if (isset(pets()[$pet])): ?>
                                    <span class="brand-card-pet" title="<?= e(pet_label($pet)) ?>"><?= icon((string) pets()[$pet]['icon'], 'icon icon-sm') ?><span class="sr-only"><?= e(pet_label($pet)) ?></span></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </span>
                        <?php if ($brand['demo_total'] === $brand['total']): ?>
                            <span class="tag tag-demo">Marca ficticia (demo)</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
