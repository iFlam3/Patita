<?php
$flat = to_cents((string) config('app.shop.shipping_flat_rate', '0'));
$free = App\Services\Cart::freeShippingFrom();
$faqs = [
    ['¿Cómo hago un pedido?', 'Agrega productos al carrito, ingresa o crea tu cuenta, completa tus datos de entrega y revisa el resumen. Al confirmar recibes un número de pedido y un correo con el detalle.'],
    ['¿Necesito una cuenta para comprar?', 'Puedes armar tu carrito sin cuenta. Para confirmar el pedido sí necesitas una cuenta: así puedes ver el historial y el estado de tus pedidos.'],
    ['¿Qué formas de pago aceptan?', 'Transferencia bancaria o pago contra entrega. No pedimos ni guardamos datos de tarjetas.'],
    ['¿Cuánto cuesta el envío?', ''],
    ['¿A qué países envían?', 'Al completar el pedido eliges el país de entrega en la lista. El costo y las condiciones se muestran antes de confirmar.'],
    ['¿Puedo cancelar o cambiar mi pedido?', 'Escríbenos con tu número de pedido antes de que sea enviado y lo revisamos. Si ya fue enviado, consulta la política de cambios y devoluciones.'],
    ['¿Cómo sé en qué estado está mi pedido?', 'En Mi cuenta, sección Mis pedidos, ves cada pedido con su estado: recibido, pago confirmado, en preparación, enviado o entregado.'],
    ['Olvidé mi contraseña, ¿qué hago?', 'Usa la opción "¿Olvidaste tu contraseña?" en la página de ingreso. Te enviaremos un enlace que vence en 60 minutos y sirve una sola vez.'],
    ['¿Venden animales?', 'No. Solo vendemos alimento, accesorios e insumos.'],
    ['¿Las guías de cuidado reemplazan al veterinario?', 'No. Son información general. Ante cualquier duda de salud, alimentación o comportamiento, consulta a un veterinario.'],
];
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/legal-head', ['heading' => 'Preguntas frecuentes', 'intro' => 'Respuestas rápidas sobre pedidos, pagos, envíos y tu cuenta.']) ?>

    <div class="faq-list">
        <?php foreach ($faqs as [$question, $answer]): ?>
            <details class="faq-item">
                <summary><?= e($question) ?></summary>
                <div class="faq-answer">
                    <?php if ($answer === ''): ?>
                        <p>El envío cuesta <?= $flat > 0 ? price_tag($flat) : 'nada' ?> por pedido<?php if ($free > 0): ?> y es gratis desde <?= price_tag($free) ?><?php endif; ?>. Verás el costo exacto en el carrito y en el resumen antes de confirmar.</p>
                    <?php else: ?>
                        <p><?= e($answer) ?></p>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <p class="faq-more">¿No encontraste tu respuesta? <a href="<?= e(url('/contacto')) ?>">Escríbenos</a>.</p>
</div>
