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
    <div class="hero-mosaic" aria-hidden="true">
        <?php foreach (['corgi', 'pug', 'kitten', 'terrier'] as $i => $photo): ?>
            <img class="hero-tile" data-reveal="scale" data-delay="<?= 2 + $i ?>"
                 src="<?= e(asset('assets/images/hero/' . $photo . '-480.jpg')) ?>"
                 srcset="<?= e(asset('assets/images/hero/' . $photo . '-480.jpg')) ?> 480w, <?= e(asset('assets/images/hero/' . $photo . '-900.jpg')) ?> 900w"
                 sizes="(min-width: 900px) 25vw, 50vw" alt="" width="480" height="600" decoding="async"<?= $i === 0 ? ' fetchpriority="high"' : '' ?>>
        <?php endforeach; ?>
    </div>
    <div class="container hero-inner">
        <div class="hero-copy">
            <h1 class="hero-title" data-split>Todo para tu mascota, sea cual sea.</h1>
            <p class="hero-lead" data-reveal data-delay="6">
                Alimento, accesorios e insumos para perros, gatos, aves, peces, reptiles, caballos y animales pequeños.
                Pagas por Pago Móvil o al recibir.
            </p>
            <div class="hero-actions" data-reveal data-delay="8">
                <a class="btn btn-accent btn-lg" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
                <a class="btn btn-outline-dark btn-lg" href="#mascotas">Elegir mascota</a>
            </div>
        </div>
    </div>
</section>

<section class="section" id="mascotas" aria-labelledby="mascotas-titulo">
    <div class="container">
        <div class="section-head">
            <h2 id="mascotas-titulo" data-split>Comprar por mascota</h2>
            <a class="link-more" href="<?= e(url('/mascotas')) ?>">Ver todas</a>
        </div>
        <ul class="pet-photos">
            <?php $n = 0; ?>
            <?php foreach (pets() as $code => $pet): ?>
                <?php $count = $petCounts[$code] ?? 0; ?>
                <li class="pet-photo" data-reveal data-delay="<?= $n++ ?>">
                    <a href="<?= e(url('/mascotas/' . $pet['slug'])) ?>">
                        <?php $photo = pet_photo($code); ?>
                        <?php if ($photo !== null): ?><img class="pet-photo-img" src="<?= e($photo) ?>" alt="" width="400" height="500" loading="lazy" decoding="async"><?php endif; ?>
                        <span class="pet-photo-text">
                            <span class="pet-photo-name"><?= icon((string) $pet['icon']) ?><?= e($pet['label']) ?></span>
                            <span class="pet-photo-count"><?= $count === 1 ? '1 producto' : $count . ' productos' ?></span>
                            <?php if (isset($pet['subtypes'])): ?>
                                <span class="pet-photo-sub"><?= e(implode(', ', array_values($pet['subtypes']))) ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section section-featured" aria-labelledby="destacados-titulo">
    <div class="container">
        <div class="section-head">
            <h2 id="destacados-titulo" data-split>Productos destacados</h2>
            <a class="link-more" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
        </div>
        <?php if ($featured === []): ?>
            <div class="state state-empty">
                <h3>Aún no hay productos publicados</h3>
                <p>Cuando el catálogo tenga productos con existencias, aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <p class="section-note">Una selección con existencias de cada tipo de mascota.</p>
            <div class="product-grid" data-reveal>
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
            <h2 id="marcas-titulo" data-split>Comprar por marca</h2>
            <a class="link-more" href="<?= e(url('/marcas')) ?>">Ver todas</a>
        </div>
        <?php if ($allDemoBrands): ?>
            <p class="section-note">Las marcas del catálogo inicial son ficticias, creadas para esta demostración.</p>
        <?php endif; ?>
        <ul class="brand-list" data-reveal>
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

<section class="section" aria-labelledby="cuida-titulo">
    <div class="container">
        <div class="section-head">
            <h2 id="cuida-titulo" data-split>Cuida mejor a tu mascota</h2>
        </div>
        <ul class="tool-grid" data-reveal>
            <li class="tool-card">
                <a href="<?= e(url('/cuidados')) ?>">
                    <span class="tool-card-icon"><?= icon('book-open') ?></span>
                    <span class="tool-card-name">Guías de cuidado</span>
                    <span class="tool-card-text">Alimentación, higiene, entorno y salud preventiva para cada especie.</span>
                </a>
            </li>
            <li class="tool-card">
                <a href="<?= e(url('/herramientas/edad')) ?>">
                    <span class="tool-card-icon"><?= icon('cake') ?></span>
                    <span class="tool-card-name">Edad en años humanos</span>
                    <span class="tool-card-text">Calcula la edad equivalente de tu perro o gato.</span>
                </a>
            </li>
            <li class="tool-card">
                <a href="<?= e(url('/herramientas/alimentos-peligrosos')) ?>">
                    <span class="tool-card-icon"><?= icon('apple') ?></span>
                    <span class="tool-card-name">Alimentos peligrosos</span>
                    <span class="tool-card-text">Chocolate, uvas, cebolla y otros alimentos peligrosos por especie.</span>
                </a>
            </li>
            <li class="tool-card">
                <a href="<?= e(url('/herramientas/peso-saludable')) ?>">
                    <span class="tool-card-icon"><?= icon('scale') ?></span>
                    <span class="tool-card-name">Peso saludable</span>
                    <span class="tool-card-text">Evalúa su condición corporal y su peso de referencia.</span>
                </a>
            </li>
        </ul>
    </div>
</section>

<section class="section section-benefits" aria-labelledby="beneficios-titulo">
    <div class="container">
        <h2 id="beneficios-titulo" class="sr-only">Cómo compras en <?= e(config('app.name')) ?></h2>
        <ul class="benefits" data-reveal>
            <li>
                <?= icon('truck', 'icon icon-lg') ?>
                <h3>Envío claro desde el carrito</h3>
                <p>Ves el costo de envío antes de confirmar.<?php if ($freeFrom > 0): ?> Gratis desde <?= price_tag($freeFrom) ?>.<?php endif; ?></p>
            </li>
            <li>
                <?= icon('banknote', 'icon icon-lg') ?>
                <h3>Sin tarjeta</h3>
                <p>Pagas por Pago Móvil o al recibir tu pedido.</p>
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
