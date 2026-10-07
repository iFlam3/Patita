<?php
/**
 * @var array<string, mixed>       $order
 * @var list<array<string, mixed>> $items
 * @var array<string, string>      $address
 */
$currency = (string) $order['currency'];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Mi cuenta', '/cuenta'], ['Mis pedidos', '/cuenta/pedidos'], [order_number((int) $order['id']), null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Pedido <?= e(order_number((int) $order['id'])) ?></h1>
            <p class="page-intro">Realizado el <?= e(format_datetime((string) $order['created_at'])) ?>. Estado: <span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></p>
        </div>
    </header>

    <div class="checkout-layout">
        <section class="review-block" aria-labelledby="pedido-productos">
            <h2 id="pedido-productos">Productos</h2>
            <ul class="review-lines">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span class="review-lines-name">
                            <?php if ($item['product_slug'] !== null && (int) $item['product_active'] === 1): ?>
                                <a href="<?= e(url('/producto/' . $item['product_slug'])) ?>"><?= e($item['product_name']) ?></a>
                            <?php else: ?>
                                <?= e($item['product_name']) ?>
                            <?php endif; ?>
                            <span class="review-lines-meta">SKU <?= e($item['sku']) ?>, <?= (int) $item['quantity'] ?> × <?= price_tag(to_cents((string) $item['unit_price']), $currency) ?></span>
                        </span>
                        <?= price_tag(to_cents((string) $item['subtotal']), $currency) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="field-hint">Los nombres y precios corresponden al momento de la compra.</p>
        </section>

        <aside class="summary-card" aria-labelledby="pedido-total">
            <h2 id="pedido-total">Totales</h2>
            <?= App\Core\View::partial('partials/totals', [
                'subtotal' => to_cents((string) $order['subtotal']),
                'shipping' => to_cents((string) $order['shipping_cost']),
                'total'    => to_cents((string) $order['total']),
                'currency' => $currency,
            ]) ?>
            <h3>Pago</h3>
            <p><?= e(payment_method_label((string) $order['payment_method'])) ?></p>
            <?php if ($order['payment_method'] === 'bank_transfer' && $order['status'] === 'received' && (string) config('app.shop.bank_details') !== ''): ?>
                <p class="payment-details"><?= e(config('app.shop.bank_details')) ?></p>
                <p class="field-hint">Referencia: <?= e(order_number((int) $order['id'])) ?></p>
            <?php endif; ?>
            <h3>Entrega</h3>
            <?= App\Core\View::partial('partials/address', ['address' => $address]) ?>
            <h3>Contacto</h3>
            <p><?= e($order['customer_name']) ?><br><?= e($order['customer_email']) ?><br><?= e($order['customer_phone']) ?></p>
        </aside>
    </div>
</div>
