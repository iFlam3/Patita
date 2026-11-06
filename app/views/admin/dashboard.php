<?php
/**
 * @var string                      $range
 * @var array<string, mixed>        $sales
 * @var array<string, int>          $stats
 * @var array<string, int>          $orderCounts
 * @var list<array<string, mixed>>  $recentOrders
 * @var int                         $userCount
 */
use App\Controllers\AdminController;

$money = static fn (float $v): string => '$' . number_format($v, 2);
$model = $sales['model'];
$unitName = ['day' => 'día', 'week' => 'semana', 'month' => 'mes'][$sales['unit']];
$change = $sales['change'];
$r2 = $model['r2'];
$byPet = [];
foreach ($sales['byPet'] as $pet => $value) {
    $byPet[$pet === 'other' ? 'Productos eliminados' : pet_label($pet)] = $value;
}
$top = [];
foreach ($sales['topProducts'] as $row) {
    $top[str_limit($row['label'], 42) . ' (' . $row['units'] . ' u.)'] = $row['revenue'];
}
$segments = [];
foreach (['received', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled'] as $status) {
    $segments[] = ['label' => order_status_label($status), 'value' => (int) ($orderCounts[$status] ?? 0), 'class' => 'seg-' . $status];
}
?>
<div class="container page">
    <header class="page-head">
        <div>
            <h1>Administración</h1>
            <p class="page-intro">Ventas, proyección de ingresos, productos y pedidos.</p>
        </div>
    </header>
    <?= App\Core\View::partial('partials/admin-nav', ['active' => 'panel']) ?>

    <?php if ($stats['other_currency'] > 0): ?>
        <p class="inline-notice inline-notice-warn"><?= icon('circle-alert', 'icon icon-sm') ?> Hay <?= $stats['other_currency'] ?> producto(s) en una moneda distinta de <?= e(config('app.currency')) ?>. No se muestran en la tienda hasta que los ajustes.</p>
    <?php endif; ?>

    <nav class="category-chips" aria-label="Periodo de las métricas">
        <ul>
            <?php foreach (AdminController::RANGES as $code => [, , $label]): ?>
                <li><a class="chip-link<?= $range === $code ? ' is-active' : '' ?>" href="<?= e(url('/admin', ['rango' => $code])) ?>"<?= $range === $code ? ' aria-current="true"' : '' ?>><?= e($label) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <ul class="kpi-grid">
        <li class="kpi">
            <span class="kpi-label">Ingresos</span>
            <span class="kpi-value"><?= e($money($sales['revenue'])) ?></span>
            <?php if ($change !== null): ?>
                <span class="kpi-delta <?= $change >= 0 ? 'is-up' : 'is-down' ?>"><?= $change >= 0 ? '▲' : '▼' ?> <?= e(number_format(abs($change), 1)) ?> % vs. periodo anterior</span>
            <?php else: ?>
                <span class="kpi-delta">Sin periodo anterior para comparar</span>
            <?php endif; ?>
        </li>
        <li class="kpi">
            <span class="kpi-label">Pedidos</span>
            <span class="kpi-value"><?= (int) $sales['orders'] ?></span>
            <span class="kpi-delta">Sin contar cancelados</span>
        </li>
        <li class="kpi">
            <span class="kpi-label">Ticket promedio</span>
            <span class="kpi-value"><?= e($money($sales['avgTicket'])) ?></span>
            <span class="kpi-delta">Ingresos ÷ pedidos</span>
        </li>
        <li class="kpi kpi-accent">
            <span class="kpi-label">Proyección próximos <?= count($sales['forecast']) ?> periodos</span>
            <span class="kpi-value"><?= e($money(array_sum($sales['forecast']))) ?></span>
            <span class="kpi-delta">Tendencia: <?= $model['b'] >= 0 ? '+' : '−' ?><?= e($money(abs($model['b']))) ?> por <?= e($unitName) ?></span>
        </li>
    </ul>

    <section class="panel" aria-labelledby="ventas-titulo">
        <div class="review-block-head">
            <h2 id="ventas-titulo">Ingresos por <?= e($unitName) ?> y proyección</h2>
            <span class="field-hint">R² = <?= $r2 === null ? '—' : e(number_format($r2, 2)) ?></span>
        </div>
        <?= App\Core\View::partial('partials/chart-line', [
            'id'           => 'grafica-ventas',
            'caption'      => 'Ingresos por ' . $unitName . ' con recta de regresión y proyección de ' . count($sales['forecast']) . ' periodos',
            'labels'       => $sales['labels'],
            'values'       => $sales['values'],
            'fitted'       => $sales['fitted'],
            'futureLabels' => $sales['futureLabels'],
            'forecast'     => $sales['forecast'],
        ]) ?>
        <p class="field-hint">
            Modelo: ingresos = <?= e(number_format($model['a'], 2)) ?> <?= $model['b'] >= 0 ? '+' : '−' ?> <?= e(number_format(abs($model['b']), 2)) ?> × periodo
            (mínimos cuadrados sobre <?= count($sales['values']) ?> periodos completos; el periodo en curso no se incluye).
            <?php if ($r2 !== null): ?>
                R² indica qué parte de la variación explica la recta: <?= $r2 >= 0.6 ? 'la tendencia es clara' : ($r2 >= 0.3 ? 'la tendencia es moderada' : 'las ventas varían mucho y la proyección es poco fiable') ?>.
            <?php endif; ?>
            Es una estimación, no una garantía.
        </p>
    </section>

    <div class="admin-columns">
        <section class="panel" aria-labelledby="top-titulo">
            <h2 id="top-titulo">Productos más vendidos</h2>
            <?= App\Core\View::partial('partials/chart-bars', ['items' => $top, 'label' => 'Ingresos por producto']) ?>
        </section>
        <section class="panel" aria-labelledby="mascota-titulo">
            <h2 id="mascota-titulo">Ingresos por mascota</h2>
            <?= App\Core\View::partial('partials/chart-bars', ['items' => $byPet, 'label' => 'Ingresos por tipo de mascota']) ?>
        </section>
    </div>

    <div class="admin-columns">
        <section class="panel" aria-labelledby="estados-titulo">
            <h2 id="estados-titulo">Pedidos por estado</h2>
            <p class="field-hint">Todos los pedidos registrados.</p>
            <?= App\Core\View::partial('partials/chart-donut', ['segments' => $segments, 'caption' => 'Pedidos por estado']) ?>
        </section>
        <section class="panel" aria-labelledby="ultimos-pedidos">
            <div class="review-block-head">
                <h2 id="ultimos-pedidos">Últimos pedidos</h2>
                <a href="<?= e(url('/admin/pedidos')) ?>">Ver todos</a>
            </div>
            <?php if ($recentOrders === []): ?>
                <p>No hay pedidos todavía.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th scope="col">Pedido</th><th scope="col">Cliente</th><th scope="col">Estado</th><th scope="col" class="num">Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <th scope="row"><a href="<?= e(url('/admin/pedidos/' . (int) $order['id'])) ?>"><?= e(order_number((int) $order['id'])) ?></a></th>
                                    <td><?= e($order['customer_name']) ?></td>
                                    <td><span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                                    <td class="num"><?= price_tag(to_cents((string) $order['total']), (string) $order['currency']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <ul class="stat-grid">
        <li class="stat"><span class="stat-value"><?= $stats['active'] ?></span><span class="stat-label">Productos publicados</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['inactive'] ?></span><span class="stat-label">Productos inactivos</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['out_of_stock'] ?></span><span class="stat-label">Publicados sin stock</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['low_stock'] ?></span><span class="stat-label">Con 5 unidades o menos</span></li>
        <li class="stat"><span class="stat-value"><?= $userCount ?></span><span class="stat-label">Cuentas registradas</span></li>
        <li class="stat"><span class="stat-value"><?= (int) ($orderCounts['received'] ?? 0) ?></span><span class="stat-label">Pedidos recibidos sin procesar</span></li>
    </ul>

    <section class="panel" aria-labelledby="correo-titulo">
        <h2 id="correo-titulo"><?= icon('mail', 'icon icon-sm') ?> Correo</h2>
        <p>Transporte: <strong><?= e(config('app.mail.transport')) ?></strong><?php if (config('app.mail.transport') === 'smtp'): ?> vía <?= e(config('app.mail.host')) ?>:<?= (int) config('app.mail.port') ?><?php endif; ?>. Remitente: <?= e(config('app.mail.from')) ?>.</p>
        <form action="<?= e(url('/admin/correo-prueba')) ?>" method="post" data-once>
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-quiet" data-loading-text="Enviando…">Enviarme un correo de prueba</button>
        </form>
    </section>
</div>
