<?php
/**
 * @var list<array{brand: string, slug: string, total: int, demo_total: int, pets: list<string>}> $brands
 */
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Marcas', null]]]) ?>
    <header class="page-head page-banner">
        <div class="page-banner-text">
            <h1 data-split>Comprar por marca</h1>
            <p class="page-intro" data-reveal data-delay="3">Marcas con productos disponibles hoy en la tienda. La lista se arma con los productos publicados.</p>
        </div>
        <div class="page-banner-media" aria-hidden="true">
            <img src="<?= e(asset('assets/images/hero/terrier-480.jpg')) ?>" alt="" width="480" height="600" decoding="async" data-reveal="scale" data-delay="2">
            <img src="<?= e(asset('assets/images/hero/corgi-480.jpg')) ?>" alt="" width="480" height="600" decoding="async" data-reveal="scale" data-delay="3">
        </div>
    </header>

    <?php if ($brands === []): ?>
        <div class="state state-empty">
            <h2>Todavía no hay marcas publicadas</h2>
            <p>Cuando haya productos activos, sus marcas aparecerán aquí.</p>
            <a class="btn btn-primary" href="<?= e(url('/catalogo')) ?>">Ir al catálogo</a>
        </div>
    <?php else: ?>
        <div class="brand-accordion">
            <?php foreach ($brands as $i => $brand): ?>
                <?php $brandUrl = '/marcas/' . $brand['slug']; ?>
                <details class="brand-acc" name="marcas"<?= $i === 0 ? ' open' : '' ?> data-reveal data-delay="<?= min($i, 6) ?>">
                    <summary class="brand-acc-head">
                        <span class="brand-acc-mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($brand['brand'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                        <span class="brand-acc-title">
                            <span class="brand-acc-name"><?= e($brand['brand']) ?></span>
                            <span class="brand-acc-count"><?= $brand['total'] === 1 ? '1 producto' : $brand['total'] . ' productos' ?></span>
                        </span>
                        <span class="brand-acc-pets">
                            <?php foreach ($brand['pets'] as $pet): ?>
                                <?php if (isset(pets()[$pet])): ?>
                                    <span class="brand-card-pet" title="<?= e(pet_label($pet)) ?>"><?= icon((string) pets()[$pet]['icon'], 'icon icon-sm') ?><span class="sr-only"><?= e(pet_label($pet)) ?></span></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </span>
                        <span class="brand-acc-toggle" aria-hidden="true"><?= icon('chevron-down') ?></span>
                    </summary>
                    <div class="brand-acc-body">
                        <ul class="brand-acc-grid">
                            <?php foreach ($brand['pets'] as $pet): ?>
                                <?php if (!isset(pets()[$pet])) { continue; } ?>
                                <?php $photo = pet_photo($pet); ?>
                                <li>
                                    <a class="brand-acc-item" href="<?= e(url($brandUrl, ['mascota' => pet_slug($pet)])) ?>">
                                        <?php if ($photo !== null): ?>
                                            <img src="<?= e($photo) ?>" alt="" width="400" height="300" loading="lazy" decoding="async">
                                        <?php endif; ?>
                                        <span class="brand-acc-label"><?= icon((string) pets()[$pet]['icon'], 'icon icon-sm') ?><?= e(pet_label($pet)) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="brand-acc-foot">
                            <a class="btn btn-accent" href="<?= e(url($brandUrl)) ?>">Ver <?= $brand['total'] === 1 ? 'el producto' : 'los ' . $brand['total'] . ' productos' ?> de <?= e($brand['brand']) ?></a>
                            <?php if ($brand['demo_total'] === $brand['total']): ?>
                                <span class="tag tag-demo">Marca ficticia (demo)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
