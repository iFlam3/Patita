<?php
/** @var list<array<string, mixed>> $orders */
?>
<div class="container page">
    <header class="page-head">
        <div><h1>Mis pedidos</h1></div>
    </header>
    <div class="account-layout">
        <?= App\Core\View::partial('partials/account-nav', ['active' => 'pedidos']) ?>
        <div class="account-main">
            <section class="panel" aria-label="Historial de pedidos">
                <?php if ($orders === []): ?>
                    <div class="state state-empty state-compact">
                        <?= icon('package', 'icon icon-xl') ?>
                        <h2>Aún no tienes pedidos</h2>
                        <p>Cuando confirmes una compra, aparecerá aquí con su estado.</p>
                        <a class="btn btn-primary" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
                    </div>
                <?php else: ?>
                    <?= App\Core\View::partial('account/orders-table', ['orders' => $orders]) ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
