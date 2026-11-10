<?php
/** Datos de Pago Móvil configurados por el administrador. */
$pm = App\Services\PagoMovil::get();
?>
<dl class="payment-details pm-details">
    <?php foreach ($pm as $key => $value): ?>
        <div><dt><?= e(App\Services\PagoMovil::LABELS[$key]) ?></dt><dd><?= e($value) ?></dd></div>
    <?php endforeach; ?>
</dl>
