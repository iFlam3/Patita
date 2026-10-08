<?php
/**
 * @var int                                                      $flatRate
 * @var int                                                      $freeFrom
 * @var array<string, string>                                     $countries
 * @var array<string, array{label: string, description: string}> $paymentMethods
 */
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Envíos y pagos', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Envíos y pagos</h1>
            <p class="page-intro">Condiciones de esta tienda de demostración. Los valores salen de la configuración y se muestran igual en el carrito.</p>
        </div>
    </header>

    <section class="panel" aria-labelledby="envios-titulo">
        <h2 id="envios-titulo"><?= icon('truck', 'icon icon-sm') ?> Envíos</h2>
        <ul class="plain-list">
            <li>Costo de envío: <?= $flatRate > 0 ? price_tag($flatRate) . ' por pedido' : 'sin costo' ?>.</li>
            <?php if ($freeFrom > 0): ?>
                <li>Envío gratis en pedidos desde <?= price_tag($freeFrom) ?>.</li>
            <?php endif; ?>
            <li>Destinos disponibles: <?= count($countries) > 20 ? count($countries) . ' países; eliges el tuyo al completar el pedido' : e(implode(', ', $countries)) ?>.</li>
            <li>Verás el costo exacto antes de confirmar el pedido.</li>
        </ul>
        <p class="field-hint">Los plazos de entrega no están definidos en esta demo. Antes de un lanzamiento real se deben acordar con una empresa de mensajería.</p>
    </section>

    <section class="panel" aria-labelledby="pagos-titulo">
        <h2 id="pagos-titulo"><?= icon('banknote', 'icon icon-sm') ?> Formas de pago</h2>
        <ul class="plain-list">
            <?php foreach ($paymentMethods as $method): ?>
                <li><strong><?= e($method['label']) ?>.</strong> <?= e($method['description']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="field-hint">Modalidad demostrativa: no se procesan cobros y nunca pedimos ni guardamos datos de tarjetas. Los impuestos aplicables dependen de cada país y no están calculados en esta demo.</p>
    </section>
</div>
