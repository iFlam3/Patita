<?php $email = (string) config('app.contact_email'); ?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/legal-head', ['heading' => 'Cambios y devoluciones', 'intro' => 'Si algo no salió como esperabas, te ayudamos.', 'updated' => true]) ?>

    <div class="prose-page">
        <h2>Plazo</h2>
        <p>Puedes solicitar un cambio o una devolución dentro de los 15 días siguientes a la entrega de tu pedido.</p>

        <h2>Condiciones</h2>
        <ul class="plain-list">
            <li>El producto debe estar sin usar, con su empaque original y sus accesorios.</li>
            <li>Por higiene y seguridad no aceptamos devoluciones de alimento, arena, champús ni productos de aseo abiertos, salvo que lleguen dañados o con defecto.</li>
            <li>Si el producto llegó dañado, con defecto o no corresponde a lo que pediste, el envío de vuelta corre por nuestra cuenta.</li>
        </ul>

        <h2>Cómo solicitarlo</h2>
        <ol class="plain-list">
            <li>Escríbenos a <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> con tu número de pedido y el motivo; si hay daños, agrega fotos.</li>
            <li>Te responderemos con las instrucciones para el cambio o la devolución.</li>
            <li>Cuando recibamos y revisemos el producto, haremos el cambio o el reembolso por el mismo medio de pago, o por transferencia si pagaste contra entrega.</li>
        </ol>

        <p>Esta política no limita los derechos que te otorgue la ley de protección al consumidor de tu país.</p>
    </div>
</div>
