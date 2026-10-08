<?php
/**
 * @var array<string, int>          $stats
 * @var array<string, int>          $orderCounts
 * @var list<array<string, mixed>>  $recentOrders
 * @var int                         $userCount
 * @var array<string, mixed>|null   $lastImport
 */
?>
<div class="container page">
    <header class="page-head">
        <div>
            <h1>Administración</h1>
            <p class="page-intro">Productos, pedidos y resultados del importador.</p>
        </div>
    </header>
    <?= App\Core\View::partial('partials/admin-nav', ['active' => 'panel']) ?>

    <?php if ($stats['other_currency'] > 0): ?>
        <p class="inline-notice inline-notice-warn"><?= icon('circle-alert', 'icon icon-sm') ?> Hay <?= $stats['other_currency'] ?> producto(s) en una moneda distinta de <?= e(config('app.currency')) ?>. No se muestran en la tienda hasta que los ajustes.</p>
    <?php endif; ?>

    <ul class="stat-grid">
        <li class="stat"><span class="stat-value"><?= $stats['active'] ?></span><span class="stat-label">Productos publicados</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['inactive'] ?></span><span class="stat-label">Productos inactivos</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['out_of_stock'] ?></span><span class="stat-label">Publicados sin stock</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['low_stock'] ?></span><span class="stat-label">Con 5 unidades o menos</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['demo'] ?></span><span class="stat-label">Datos de demostración</span></li>
        <li class="stat"><span class="stat-value"><?= $stats['scraped'] ?></span><span class="stat-label">Importados de fuentes</span></li>
        <li class="stat"><span class="stat-value"><?= $userCount ?></span><span class="stat-label">Cuentas registradas</span></li>
        <li class="stat"><span class="stat-value"><?= (int) ($orderCounts['received'] ?? 0) ?></span><span class="stat-label">Pedidos recibidos sin procesar</span></li>
    </ul>

    <div class="admin-columns">
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

        <section class="panel" aria-labelledby="ultima-importacion">
            <div class="review-block-head">
                <h2 id="ultima-importacion">Última importación</h2>
                <a href="<?= e(url('/admin/importador')) ?>">Ver importador</a>
            </div>
            <?php if ($lastImport === null): ?>
                <p>Aún no se ejecutó el importador. Las instrucciones están en la sección Importador.</p>
            <?php else: ?>
                <p><strong><?= e($lastImport['mode'] ?? '') ?></strong> <?= e($lastImport['source'] ?? '') ?><?= !empty($lastImport['dry_run']) ? ' (simulación)' : '' ?></p>
                <p class="field-hint"><?= e(format_datetime((string) ($lastImport['finished_at'] ?? ''))) ?></p>
                <?php $c = (array) ($lastImport['counts'] ?? []); ?>
                <p><?= (int) ($c['created'] ?? 0) ?> creados, <?= (int) ($c['updated'] ?? 0) ?> actualizados, <?= (int) ($c['skipped'] ?? 0) ?> omitidos, <?= (int) ($c['error'] ?? 0) ?> con error.</p>
            <?php endif; ?>
        </section>
        <section class="panel" aria-labelledby="correo-titulo">
            <h2 id="correo-titulo"><?= icon('mail', 'icon icon-sm') ?> Correo</h2>
            <p>Transporte: <strong><?= e(config('app.mail.transport')) ?></strong><?php if (config('app.mail.transport') === 'smtp'): ?> vía <?= e(config('app.mail.host')) ?>:<?= (int) config('app.mail.port') ?><?php endif; ?>. Remitente: <?= e(config('app.mail.from')) ?>.</p>
            <form action="<?= e(url('/admin/correo-prueba')) ?>" method="post" data-once>
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-quiet" data-loading-text="Enviando…">Enviarme un correo de prueba</button>
            </form>
        </section>
    </div>
</div>
