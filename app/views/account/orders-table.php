<?php
/** @var list<array<string, mixed>> $orders */
?>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Pedido</th>
                <th scope="col">Fecha</th>
                <th scope="col">Estado</th>
                <th scope="col" class="num">Unidades</th>
                <th scope="col" class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <th scope="row"><a href="<?= e(url('/cuenta/pedidos/' . (int) $order['id'])) ?>"><?= e(order_number((int) $order['id'])) ?></a></th>
                    <td><?= e(format_datetime((string) $order['created_at'])) ?></td>
                    <td><span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                    <td class="num"><?= (int) $order['units'] ?></td>
                    <td class="num"><?= price_tag(to_cents((string) $order['total']), (string) $order['currency']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
