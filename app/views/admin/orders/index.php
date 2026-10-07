<?php
/**
 * @var string               $status
 * @var array<string, int>   $counts
 * @var array<string, mixed> $result
 */
$query = $status !== '' ? ['estado' => $status] : [];
?>
<div class="container page">
    <header class="page-head">
        <div>
            <h1>Pedidos</h1>
            <p class="page-intro">Consulta pedidos y actualiza su estado.</p>
        </div>
    </header>
    <?= App\Core\View::partial('partials/admin-nav', ['active' => 'pedidos']) ?>

    <nav class="category-chips" aria-label="Filtrar por estado">
        <ul>
            <li><a class="chip-link<?= $status === '' ? ' is-active' : '' ?>" href="<?= e(url('/admin/pedidos')) ?>"<?= $status === '' ? ' aria-current="true"' : '' ?>>Todos <span class="chip-count"><?= array_sum($counts) ?></span></a></li>
            <?php foreach ((array) config('catalog.order_statuses') as $code => $info): ?>
                <li>
                    <a class="chip-link<?= $status === $code ? ' is-active' : '' ?>" href="<?= e(url('/admin/pedidos', ['estado' => $code])) ?>"<?= $status === $code ? ' aria-current="true"' : '' ?>>
                        <?= e($info['label']) ?> <span class="chip-count"><?= (int) ($counts[$code] ?? 0) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <?php if ($result['items'] === []): ?>
        <div class="state state-empty state-compact">
            <h2>No hay pedidos<?= $status !== '' ? ' con ese estado' : '' ?></h2>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Pago</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $order): ?>
                        <tr>
                            <th scope="row"><a href="<?= e(url('/admin/pedidos/' . (int) $order['id'])) ?>"><?= e(order_number((int) $order['id'])) ?></a></th>
                            <td><?= e(format_datetime((string) $order['created_at'])) ?></td>
                            <td><?= e($order['customer_name']) ?><span class="cell-sub"><?= e($order['customer_email']) ?></span></td>
                            <td><?= e(payment_method_label((string) $order['payment_method'])) ?></td>
                            <td><span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                            <td class="num"><?= price_tag(to_cents((string) $order['total']), (string) $order['currency']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= App\Core\View::partial('partials/pagination', [
            'page' => $result['page'], 'pages' => $result['pages'], 'base' => '/admin/pedidos', 'query' => $query,
        ]) ?>
    <?php endif; ?>
</div>
