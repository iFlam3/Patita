<?php
$name = (string) config('app.name');
$email = (string) config('app.contact_email');
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/legal-head', ['heading' => 'Política de privacidad', 'intro' => 'Qué datos tuyos usamos, para qué y cómo puedes controlarlos.', 'updated' => true]) ?>

    <div class="prose-page">
        <h2>Datos que recopilamos</h2>
        <ul class="plain-list">
            <li><strong>Cuenta:</strong> nombre, correo y contraseña (guardada cifrada; nadie puede leerla).</li>
            <li><strong>Pedidos:</strong> nombre, correo, teléfono, dirección de entrega, productos, importes y forma de pago elegida.</li>
            <li><strong>Datos técnicos:</strong> dirección IP y registros de seguridad, usados para proteger tu cuenta frente a intentos de acceso no autorizados.</li>
        </ul>
        <p>No recopilamos datos de tarjetas de pago.</p>

        <h2>Para qué los usamos</h2>
        <ul class="plain-list">
            <li>Gestionar tu cuenta, tus pedidos y sus entregas.</li>
            <li>Enviarte correos sobre tus pedidos y la recuperación de tu contraseña.</li>
            <li>Proteger el sitio y prevenir fraudes.</li>
        </ul>
        <p>No vendemos tus datos ni los usamos para publicidad de terceros.</p>

        <h2>Con quién los compartimos</h2>
        <p>Solo con quienes los necesitan para cumplir tu pedido (por ejemplo, la empresa de mensajería recibe tu nombre, teléfono y dirección) o cuando la ley lo exige.</p>

        <h2>Cookies</h2>
        <p>Usamos una cookie técnica de sesión, necesaria para mantener tu carrito y tu sesión iniciada. No usamos cookies de publicidad. Las tipografías del sitio se cargan desde Google Fonts, lo que implica que tu navegador se conecta a servidores de Google.</p>

        <h2>Cuánto tiempo los guardamos</h2>
        <p>Los datos de tu cuenta, mientras la mantengas activa. Los datos de pedidos, durante el tiempo que exijan las obligaciones contables y legales aplicables.</p>

        <h2>Tus derechos</h2>
        <p>Puedes consultar y corregir tu nombre y correo en <a href="<?= e(url('/cuenta')) ?>">Mi cuenta</a>. Para solicitar acceso, rectificación, eliminación u oposición al uso de tus datos, escríbenos a <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>

        <h2>Seguridad</h2>
        <p>Protegemos tu información con contraseñas cifradas, conexiones seguras y controles de acceso. Ningún sistema es infalible: si detectamos un incidente que afecte tus datos, te lo informaremos.</p>

        <h2>Cambios</h2>
        <p><?= e($name) ?> puede actualizar esta política. Publicaremos la versión vigente en esta página con su fecha de actualización.</p>
    </div>
</div>
