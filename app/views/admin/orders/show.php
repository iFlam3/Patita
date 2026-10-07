<?php
/**
 * @var array<string, mixed>       $order
 * @var list<array<string, mixed>> $items
 * @var array<string, string>      $address
 * @var list<string>               $transitions
 */
$currency = (string) $order['currency'];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Administración', '/admin'], ['Pedidos', '/admin/pedidos'], [order_number((int) $order['id']), null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Pedido <?= e(order_number((int) $order['id'])) ?></h1>
            <p class="page-intro">Creado el <?= e(format_datetime((string) $order['created_at'])) ?>, actualizado el <?= e(format_datetime((string) $order['updated_at'])) ?>.</p>
        </div>
    </header>

    <div class="checkout-layout">
        <div class="review-sections">
            <section class="review-block" aria-labelledby="adm-productos">
                <h2 id="adm-productos">Productos</h2>
                <ul class="review-lines">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <span class="review-lines-name">
                                <?= e($item['product_name']) ?>
                                <span class="review-lines-meta">SKU <?= e($item['sku']) ?>, <?= (int) $item['quantity'] ?> × <?= price_tag(to_cents((string) $item['unit_price']), $currency) ?><?= $item['product_id'] === null ? ', producto eliminado' : '' ?></span>
                            </span>
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
            </section>

            <section class="review-block" aria-labelledby="adm-cliente">
                <h2 id="adm-cliente">Cliente y entrega</h2>
                <div class="review-columns">
                    <div>
                        <h3>Contacto</h3>
                        <p><?= e($order['customer_name']) ?><br><?= e($order['customer_email']) ?><br><?= e($order['customer_phone']) ?></p>
                    </div>
                    <div>
                        <h3>Dirección</h3>
                        <?= App\Core\View::partial('partials/address', ['address' => $address]) ?>
                    </div>
                </div>
                <h3>Pago</h3>
                <p><?= e(payment_method_label((string) $order['payment_method'])) ?> (demostrativo, sin cobro)</p>
            </section>
        </div>

        <aside class="summary-card" aria-labelledby="adm-estado">
            <h2 id="adm-estado">Estado</h2>
            <p><span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></p>
            <?php if ($transitions === []): ?>
                <p class="field-hint">Este estado es final y ya no admite cambios.</p>
            <?php else: ?>
                <form action="<?= e(url('/admin/pedidos/' . (int) $order['id'] . '/estado')) ?>" method="post" data-once>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="o-status">Cambiar a</label>
                        <select id="o-status" name="status">
                            <?php foreach ($transitions as $status): ?>
                                <option value="<?= e($status) ?>"><?= e(order_status_label($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" data-confirm-when="cancelled" data-confirm="¿Cancelar el pedido? Se devolverán las unidades al stock y no podrás reabrirlo.">Actualizar estado</button>
                </form>
                <p class="field-hint">Entregado y Cancelado son estados finales. Al cancelar se devuelven las unidades al stock.</p>
            <?php endif; ?>
        </aside>
    </div>
</div>
