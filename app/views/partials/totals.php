<?php
/**
 * Bloque de totales (carrito, checkout, pedidos).
 *
 * @var int    $subtotal Centavos.
 * @var int    $shipping Centavos.
 * @var int    $total    Centavos.
 * @var string $currency
 */
?>
<dl class="totals">
    <div class="totals-row">
        <dt>Subtotal</dt>
        <dd><?= price_tag($subtotal, $currency) ?></dd>
    </div>
    <div class="totals-row">
        <dt>Envío</dt>
        <dd><?= $shipping === 0 && $subtotal > 0 ? '<span class="totals-free">Gratis</span>' : price_tag($shipping, $currency) ?></dd>
    </div>
    <div class="totals-row totals-total">
        <dt>Total</dt>
        <dd><?= price_tag($total, $currency) ?></dd>
    </div>
</dl>
