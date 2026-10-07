<?php
/**
 * @var array<string, mixed>       $order
 * @var list<array<string, mixed>> $items
 * @var array<string, string>      $address
 */
$number = order_number((int) $order['id']);
$currency = (string) $order['currency'];
$isTransfer = $order['payment_method'] === 'bank_transfer';
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/checkout-steps', ['current' => 4]) ?>

    <section class="confirmation">
        <span class="confirmation-icon"><?= icon('check', 'icon icon-xl') ?></span>
        <h1>Recibimos tu pedido</h1>
        <p class="confirmation-number">Número de pedido: <strong><?= e($number) ?></strong></p>
        <p>Te enviamos el resumen a <?= e($order['customer_email']) ?>. Estado actual: <span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></p>
    </section>

    <section class="payment-box" aria-labelledby="instrucciones-pago">
        <h2 id="instrucciones-pago"><?= icon($isTransfer ? 'banknote' : 'hand-coins', 'icon icon-sm') ?> <?= e(payment_method_label((string) $order['payment_method'])) ?></h2>
        <?php if ($isTransfer): ?>
            <p>Transfiere <?= price_tag(to_cents((string) $order['total']), $currency) ?> y usa <strong><?= e($number) ?></strong> como referencia.</p>
            <?php if ((string) config('app.shop.bank_details') !== ''): ?>
                <p class="payment-details"><?= e(config('app.shop.bank_details')) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p>Pagarás <?= price_tag(to_cents((string) $order['total']), $currency) ?> al recibir tu pedido.</p>
        <?php endif; ?>
        <p class="field-hint">Modalidad demostrativa: no se procesa ningún cobro.</p>
    </section>

    <section class="review-block" aria-labelledby="detalle-pedido">
        <h2 id="detalle-pedido">Detalle</h2>
        <ul class="review-lines">
            <?php foreach ($items as $item): ?>
                <li>
                    <span class="review-lines-name"><?= e($item['product_name']) ?><span class="review-lines-meta"><?= (int) $item['quantity'] ?> × <?= price_tag(to_cents((string) $item['unit_price']), $currency) ?></span></span>
                    <?= price_tag(to_cents((string) $item['subtotal']), $currency) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?= App\Core\View::partial('partials/totals', [
            'subtotal' => to_cents((string) $order['subtotal']),
            'shipping' => to_cents((string) $order['shipping_cost']),
            'total'    => to_cents((string) $order['total']),
            'currency' => $currency,
        ]) ?>
        <h3>Entrega</h3>
        <?= App\Core\View::partial('partials/address', ['address' => $address]) ?>
    </section>

    <div class="state-actions">
        <a class="btn btn-primary" href="<?= e(url('/cuenta/pedidos/' . (int) $order['id'])) ?>">Ver el pedido en mi cuenta</a>
        <a class="btn btn-quiet" href="<?= e(url('/catalogo')) ?>">Seguir comprando</a>
    </div>
</div>
