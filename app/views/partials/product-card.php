<?php
/**
 * Tarjeta de producto.
 *
 * @var array<string, mixed> $product
 * @var string|null          $returnTo Ruta a la que vuelve el formulario sin JavaScript.
 */
$slugUrl = url('/producto/' . $product['slug']);
$stock = (int) $product['stock'];
$returnTo = $returnTo ?? '/carrito';
$headingLevel = $headingLevel ?? 'h3';
?>
<article class="product-card">
    <a class="product-card-media" href="<?= e($slugUrl) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e(product_image((string) $product['image_url'], (string) $product['pet_type'], (string) $product['category'])) ?>"
             alt="" width="400" height="300" loading="lazy" decoding="async">
    </a>
    <?php if ($product['data_origin'] === 'demo'): ?>
        <span class="tag tag-demo" title="Producto de demostración">Demo</span>
    <?php endif; ?>
    <div class="product-card-body">
        <p class="product-card-pet"><?= e(pet_label((string) $product['pet_type'])) ?></p>
        <<?= $headingLevel ?> class="product-card-title">
            <a href="<?= e($slugUrl) ?>"><?= e($product['name']) ?></a>
        </<?= $headingLevel ?>>
        <p class="product-card-brand"><?= e($product['brand']) ?></p>
        <div class="product-card-foot">
            <?= price_tag(to_cents((string) $product['price']), (string) $product['currency'], 'price product-card-price') ?>
            <?php if ($stock <= 0): ?>
                <span class="stock stock-out">Agotado</span>
            <?php elseif ($stock <= 5): ?>
                <span class="stock stock-low">Quedan <?= $stock ?></span>
            <?php else: ?>
                <span class="stock stock-in">Disponible</span>
            <?php endif; ?>
        </div>
        <?php if ($stock > 0): ?>
            <form class="product-card-form" action="<?= e(url('/carrito/agregar')) ?>" method="post" data-add-to-cart>
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                <button type="submit" class="btn btn-primary btn-block">
                    <?= icon('plus', 'icon icon-sm') ?> Agregar<span class="sr-only"> <?= e($product['name']) ?> al carrito</span>
                </button>
            </form>
        <?php else: ?>
            <a class="btn btn-quiet btn-block" href="<?= e($slugUrl) ?>">Ver detalles<span class="sr-only"> de <?= e($product['name']) ?></span></a>
        <?php endif; ?>
    </div>
</article>
