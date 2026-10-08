<?php
$email = (string) config('app.contact_email');
$phone = (string) config('app.contact_phone');
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/legal-head', ['heading' => 'Contacto', 'intro' => 'Escríbenos sobre pedidos, productos, cambios o cualquier duda.']) ?>

    <ul class="contact-grid">
        <li class="panel">
            <h2><?= icon('mail', 'icon icon-sm') ?> Correo</h2>
            <p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
            <p class="field-hint">Si es sobre un pedido, incluye su número (por ejemplo, PAT-000123).</p>
        </li>
        <?php if ($phone !== ''): ?>
            <li class="panel">
                <h2><?= icon('messages-square', 'icon icon-sm') ?> Teléfono</h2>
                <p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></p>
            </li>
        <?php endif; ?>
        <li class="panel">
            <h2><?= icon('info', 'icon icon-sm') ?> Respuestas rápidas</h2>
            <p>Muchas dudas sobre pagos, envíos y cuentas están resueltas en las <a href="<?= e(url('/preguntas-frecuentes')) ?>">preguntas frecuentes</a>.</p>
        </li>
    </ul>
</div>
