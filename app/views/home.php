<?php
/**
 * @var array<string, int>               $petCounts
 * @var list<array<string, mixed>>       $featured
 * @var list<array<string, mixed>>       $brands
 */
$freeFrom = App\Services\Cart::freeShippingFrom();
$allDemoBrands = $brands !== [] && array_sum(array_column($brands, 'demo_total')) === array_sum(array_column($brands, 'total'));
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy">
            <h1 class="hero-title">Todo para tu mascota, sea cual sea.</h1>
            <p class="hero-lead">
                Alimento, accesorios e insumos para perros, gatos, aves, peces, reptiles, caballos y animales pequeños.
                Pagas por transferencia o al recibir.
            </p>
            <div class="hero-actions">
                <a class="btn btn-light btn-lg" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
                <a class="btn btn-outline-light btn-lg" href="#mascotas">Elegir mascota</a>
            </div>
        </div>
        <div class="hero-ark" aria-hidden="true">
            <ul class="hero-ark-animals">
                <?php foreach (pets() as $pet): ?>
                    <li><?= icon((string) $pet['icon']) ?></li>
                <?php endforeach; ?>
            </ul>
            <svg class="hero-ark-hull" viewBox="0 0 400 112" focusable="false">
                <path class="hull" d="M8 8H392L344 78Q338 86 328 86H72Q62 86 56 78Z"/>
                <path class="hull-stripe" d="M32 30H368"/>
                <path class="waves" d="M0 104q25-10 50 0t50 0 50 0 50 0 50 0 50 0 50 0 50 0"/>
            </svg>
        </div>
    </div>
</section>

<section class="section" id="mascotas" aria-labelledby="mascotas-titulo">
    <div class="container">
        <div class="section-head">
            <h2 id="mascotas-titulo">Comprar por mascota</h2>
            <a class="link-more" href="<?= e(url('/mascotas')) ?>">Ver todas</a>
        </div>
        <ul class="pet-grid">
            <?php foreach (pets() as $code => $pet): ?>
                <?php $count = $petCounts[$code] ?? 0; ?>
                <li class="pet-card<?= isset($pet['subtypes']) ? ' pet-card-wide' : '' ?>">
                    <a href="<?= e(url('/mascotas/' . $pet['slug'])) ?>">
                        <span class="pet-card-icon"><?= icon((string) $pet['icon']) ?></span>
                        <span class="pet-card-text">
                            <span class="pet-card-name"><?= e($pet['label']) ?></span>
                            <span class="pet-card-count"><?= $count === 1 ? '1 producto' : $count . ' productos' ?></span>
                            <?php if (isset($pet['subtypes'])): ?>
                                <span class="pet-card-sub"><?= e(implode(', ', array_values($pet['subtypes']))) ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section section-tint" aria-labelledby="destacados-titulo">
    <div class="container">
        <div class="section-head">
            <h2 id="destacados-titulo">Productos destacados</h2>
            <a class="link-more" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
        </div>
        <?php if ($featured === []): ?>
            <div class="state state-empty">
                <h3>Aún no hay productos publicados</h3>
                <p>Cuando el catálogo tenga productos con existencias, aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <p class="section-note">Una selección con existencias de cada tipo de mascota.</p>
            <div class="product-grid">
                <?php foreach ($featured as $product): ?>
                    <?= App\Core\View::partial('partials/product-card', ['product' => $product, 'returnTo' => '/']) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" aria-labelledby="marcas-titulo">
    <div class="container">
        <div class="section-head">
            <h2 id="marcas-titulo">Comprar por marca</h2>
            <a class="link-more" href="<?= e(url('/marcas')) ?>">Ver todas</a>
        </div>
        <?php if ($allDemoBrands): ?>
            <p class="section-note">Las marcas del catálogo inicial son ficticias, creadas para esta demostración.</p>
        <?php endif; ?>
        <ul class="brand-list">
            <?php foreach ($brands as $brand): ?>
                <li>
                    <a href="<?= e(url('/marcas/' . $brand['slug'])) ?>">
                        <span class="brand-list-name"><?= e($brand['brand']) ?></span>
                        <span class="brand-list-count"><?= (int) $brand['total'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section section-benefits" aria-labelledby="beneficios-titulo">
    <div class="container">
        <h2 id="beneficios-titulo" class="sr-only">Cómo compras en <?= e(config('app.name')) ?></h2>
        <ul class="benefits">
            <li>
                <?= icon('truck', 'icon icon-lg') ?>
                <h3>Envío claro desde el carrito</h3>
                <p>Ves el costo de envío antes de confirmar.<?php if ($freeFrom > 0): ?> Gratis desde <?= price_tag($freeFrom) ?>.<?php endif; ?></p>
            </li>
            <li>
                <?= icon('banknote', 'icon icon-lg') ?>
                <h3>Sin tarjeta</h3>
                <p>Pagas por transferencia bancaria o al recibir tu pedido.</p>
            </li>
            <li>
                <?= icon('paw-print', 'icon icon-lg') ?>
                <h3>Siete tipos de mascota</h3>
                <p>De perros y gatos a reptiles, caballos, conejos y hurones.</p>
            </li>
            <li>
                <?= icon('info', 'icon icon-lg') ?>
                <h3>Datos sin adornos</h3>
                <p>Si un dato del producto no está disponible, te lo decimos en la ficha.</p>
            </li>
        </ul>
    </div>
</section>
