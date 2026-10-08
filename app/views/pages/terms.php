<?php
$name = (string) config('app.name');
$country = country_name((string) config('app.country'));
$email = (string) config('app.contact_email');
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/legal-head', ['heading' => 'Términos y condiciones', 'intro' => 'Las reglas para usar el sitio y comprar en ' . $name . '.', 'updated' => true]) ?>

    <div class="prose-page">
        <h2>1. Aceptación</h2>
        <p>Al usar este sitio o hacer un pedido aceptas estos términos. Si no estás de acuerdo con ellos, no uses el sitio.</p>

        <h2>2. Cuenta</h2>
        <p>Para confirmar pedidos necesitas una cuenta con datos verdaderos. Eres responsable de mantener tu contraseña en secreto y de la actividad de tu cuenta. Avísanos si sospechas un uso no autorizado.</p>

        <h2>3. Productos, precios y disponibilidad</h2>
        <p>Los precios se muestran en <?= e(config('app.currency')) ?> e incluyen el costo de envío solo cuando así se indica en el resumen del pedido. Las existencias pueden cambiar; si un producto se agota antes de confirmar tu pedido, te lo indicaremos y no se incluirá. Las imágenes son ilustrativas.</p>

        <h2>4. Pedidos</h2>
        <p>Recibirás un número de pedido al confirmar. Podemos cancelar un pedido por falta de existencias, error evidente de precio o sospecha de fraude; en ese caso te avisaremos y, si ya pagaste, te devolveremos el importe.</p>

        <h2>5. Pagos</h2>
        <p>Aceptamos transferencia bancaria y pago contra entrega. No solicitamos ni almacenamos datos de tarjetas. En pagos por transferencia, el pedido se prepara cuando confirmamos el pago.</p>

        <h2>6. Envíos</h2>
        <p>El costo de envío se muestra antes de confirmar. Los plazos dependen del destino y de la empresa de mensajería. Revisa que la dirección sea correcta: un error en ella puede generar costos adicionales.</p>

        <h2>7. Cambios y devoluciones</h2>
        <p>Consulta nuestra <a href="<?= e(url('/devoluciones')) ?>">política de cambios y devoluciones</a>. Esa política no limita los derechos que te otorgue la ley de protección al consumidor aplicable.</p>

        <h2>8. Información de cuidado y herramientas</h2>
        <p>Las guías de cuidado, fichas de producto y herramientas (edad, alimentos peligrosos y peso saludable) son información general y estimaciones orientativas. No reemplazan la consulta con un veterinario.</p>

        <h2>9. Propiedad intelectual</h2>
        <p>Los textos, logotipos, ilustraciones y el diseño del sitio pertenecen a <?= e($name) ?> o a sus titulares. Las marcas de terceros pertenecen a sus respectivos dueños.</p>

        <h2>10. Cambios a estos términos</h2>
        <p>Podemos actualizar estos términos. La versión vigente es la publicada en esta página, con su fecha de actualización. Los pedidos confirmados se rigen por los términos vigentes al momento de la compra.</p>

        <h2>11. Ley aplicable y contacto</h2>
        <p>Estos términos se rigen por las leyes de <?= e($country) ?>. Para cualquier consulta, escríbenos a <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
    </div>
</div>
