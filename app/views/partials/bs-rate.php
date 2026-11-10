<?php
/**
 * Botón que consulta la tasa del dólar paralelo (ve.dolarapi.com) y muestra
 * el total en Bs. Mejora progresiva: sin JavaScript no se muestra.
 *
 * @var int $total Centavos en USD.
 */
?>
<div class="bs-rate" data-bs-rate data-usd="<?= e(cents_to_decimal($total)) ?>" hidden>
    <button type="button" class="btn btn-quiet btn-sm" data-bs-rate-btn><?= icon('calculator', 'icon icon-sm') ?> Ver total en Bs (tasa del día)</button>
    <div class="bs-rate-result" data-bs-rate-out aria-live="polite"></div>
</div>
