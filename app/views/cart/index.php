<?php
/**
 * @var array<string, mixed> $summary
 */
$lines = $summary['lines'];
$currency = (string) $summary['currency'];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/checkout-steps', ['current' => 1]) ?>
    <header class="page-head">
        <div><h1>Tu carrito</h1></div>
    </header>

    <?php foreach ($summary['notices'] as $notice): ?>
        <p class="inline-notice inline-notice-warn" role="status"><?= icon('circle-alert', 'icon icon-sm') ?> <?= e($notice) ?></p>
    <?php endforeach; ?>

    <?php if ($lines === []): ?>
        <div class="state state-empty">
            <?= icon('shopping-bag', 'icon icon-xl') ?>
            <h2>Tu carrito está vacío</h2>
            <p>Explora el catálogo o elige una mascota para empezar.</p>
            <div class="state-actions">
                <a class="btn btn-primary" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
                <a class="btn btn-quiet" href="<?= e(url('/mascotas')) ?>">Comprar por mascota</a>
            </div>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <section aria-labelledby="productos-carrito">
                <h2 id="productos-carrito" class="sr-only">Productos</h2>
                <ul class="cart-lines">
                    <?php foreach ($lines as $line): ?>
                        <?php $p = $line['product']; $inputId = 'qty-' . (int) $p['id']; ?>
                        <li class="cart-line">
                            <img class="cart-line-img" src="<?= e(product_image((string) $p['image_url'], (string) $p['pet_type'], (string) $p['category'])) ?>" alt="" width="96" height="72" loading="lazy">
                            <div class="cart-line-info">
                                <a class="cart-line-name" href="<?= e(url('/producto/' . $p['slug'])) ?>"><?= e($p['name']) ?></a>
                                <p class="cart-line-meta"><?= e($p['brand']) ?>, <?= price_tag($line['unit_cents'], $currency) ?> c/u</p>
                            </div>
                            <form class="cart-line-qty" action="<?= e(url('/carrito/actualizar')) ?>" method="post" data-cart-update>
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                <label for="<?= e($inputId) ?>">Cantidad</label>
                                <div class="qty-control" data-qty>
                                    <button type="button" class="qty-btn" data-qty-step="-1" aria-label="Quitar una unidad de <?= e($p['name']) ?>"><?= icon('minus', 'icon icon-sm') ?></button>
                                    <input id="<?= e($inputId) ?>" type="number" name="quantity" value="<?= (int) $line['quantity'] ?>" min="1" max="<?= (int) $line['max'] ?>" step="1" inputmode="numeric" required data-autosubmit>
                                    <button type="button" class="qty-btn" data-qty-step="1" aria-label="Agregar una unidad de <?= e($p['name']) ?>"><?= icon('plus', 'icon icon-sm') ?></button>
                                </div>
                                <button type="submit" class="btn btn-quiet btn-sm js-hidden">Actualizar</button>
                            </form>
                            <p class="cart-line-total"><?= price_tag($line['total_cents'], $currency) ?></p>
                            <form class="cart-line-remove" action="<?= e(url('/carrito/eliminar')) ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="btn-icon" aria-label="Quitar <?= e($p['name']) ?> del carrito"><?= icon('trash-2') ?></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="link-back" href="<?= e(url('/catalogo')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Seguir comprando</a>
            </section>

            <aside class="summary-card" aria-labelledby="resumen-carrito">
                <h2 id="resumen-carrito">Resumen</h2>
                <?= App\Core\View::partial('partials/totals', [
                    'subtotal' => $summary['subtotal_cents'],
                    'shipping' => $summary['shipping_cents'],
                    'total'    => $summary['total_cents'],
                    'currency' => $currency,
                ]) ?>
                <?php if ($summary['free_shipping_remaining_cents'] > 0): ?>
                    <p class="summary-hint"><?= icon('truck', 'icon icon-sm') ?> Te faltan <?= price_tag($summary['free_shipping_remaining_cents'], $currency) ?> para el envío gratis.</p>
                <?php endif; ?>
                <a class="btn btn-primary btn-lg btn-block" href="<?= e(url('/checkout')) ?>">Continuar con el pedido</a>
                <?php if (auth_user() === null): ?>
                    <p class="summary-hint">Te pediremos ingresar o crear una cuenta para confirmar.</p>
                <?php endif; ?>
                <p class="summary-hint">Los precios y el stock se confirman de nuevo al finalizar.</p>
            </aside>
        </div>
    <?php endif; ?>
</div>
