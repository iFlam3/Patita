<?php
/**
 * @var array<string, mixed> $summary
 * @var array<string, mixed> $data
 * @var string               $checkoutToken
 */
$currency = (string) $summary['currency'];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/checkout-steps', ['current' => 3]) ?>
    <header class="page-head">
        <div>
            <h1>Revisa tu pedido</h1>
            <p class="page-intro">Confirma que todo esté bien. Al confirmar reservamos las existencias.</p>
        </div>
    </header>

    <?php foreach ($summary['notices'] as $notice): ?>
        <p class="inline-notice inline-notice-warn" role="status"><?= icon('circle-alert', 'icon icon-sm') ?> <?= e($notice) ?></p>
    <?php endforeach; ?>

    <div class="checkout-layout">
        <div class="review-sections">
            <section class="review-block" aria-labelledby="rev-productos">
                <div class="review-block-head">
                    <h2 id="rev-productos">Productos</h2>
                    <a href="<?= e(url('/carrito')) ?>">Editar carrito</a>
                </div>
                <ul class="review-lines">
                    <?php foreach ($summary['lines'] as $line): ?>
                        <?php $p = $line['product']; ?>
                        <li>
                            <img src="<?= e(product_image((string) $p['image_url'], (string) $p['pet_type'], (string) $p['category'])) ?>" alt="" width="64" height="48" loading="lazy">
                            <span class="review-lines-name"><?= e($p['name']) ?><span class="review-lines-meta"><?= (int) $line['quantity'] ?> × <?= price_tag($line['unit_cents'], $currency) ?></span></span>
                            <?= price_tag($line['total_cents'], $currency) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="review-block" aria-labelledby="rev-entrega">
                <div class="review-block-head">
                    <h2 id="rev-entrega">Entrega y contacto</h2>
                    <a href="<?= e(url('/checkout')) ?>">Editar datos</a>
                </div>
                <div class="review-columns">
                    <div>
                        <h3>Dirección</h3>
                        <?= App\Core\View::partial('partials/address', ['address' => (array) $data['address']]) ?>
                    </div>
                    <div>
                        <h3>Contacto</h3>
                        <p><?= e($data['name']) ?><br><?= e($data['email']) ?><br><?= e($data['phone']) ?></p>
                    </div>
                </div>
            </section>

            <section class="review-block" aria-labelledby="rev-pago">
                <div class="review-block-head">
                    <h2 id="rev-pago">Forma de pago</h2>
                    <a href="<?= e(url('/checkout')) ?>">Cambiar</a>
                </div>
                <p><strong><?= e(payment_method_label((string) $data['payment_method'])) ?></strong></p>
                <p class="field-hint">Modalidad demostrativa: no se realiza ningún cobro.</p>
            </section>
        </div>

        <aside class="summary-card" aria-labelledby="rev-total">
            <h2 id="rev-total">Total a pagar</h2>
            <?= App\Core\View::partial('partials/totals', [
                'subtotal' => $summary['subtotal_cents'],
                'shipping' => $summary['shipping_cents'],
                'total'    => $summary['total_cents'],
                'currency' => $currency,
            ]) ?>
            <form action="<?= e(url('/checkout/confirmar')) ?>" method="post" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="checkout_token" value="<?= e($checkoutToken) ?>">
                <input type="hidden" name="cart_fingerprint" value="<?= e($summary['fingerprint']) ?>">
                <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Confirmando…">Confirmar pedido</button>
            </form>
            <p class="summary-hint">Todos los importes están en <?= e($currency) ?> e incluyen el envío mostrado.</p>
        </aside>
    </div>
</div>
