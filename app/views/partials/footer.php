<?php $user = auth_user(); ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand-on-dark" href="<?= e(url('/')) ?>">
                <img class="brand-mark" src="<?= e(asset('assets/images/logo-light.svg')) ?>" alt="" width="36" height="36">
                <span class="brand-name"><?= e(config('app.name')) ?></span>
            </a>
            <p>Todo para tu mascota, sea cual sea. Precios en <?= e(config('app.currency')) ?> para <?= e(country_name((string) config('app.country'))) ?>.</p>
        </div>
        <nav aria-label="Comprar">
            <h2 class="footer-title">Comprar</h2>
            <ul>
                <li><a href="<?= e(url('/catalogo')) ?>">Catálogo completo</a></li>
                <li><a href="<?= e(url('/mascotas')) ?>">Comprar por mascota</a></li>
                <li><a href="<?= e(url('/marcas')) ?>">Comprar por marca</a></li>
                <li><a href="<?= e(url('/envios-y-pagos')) ?>">Envíos y pagos</a></li>
            </ul>
        </nav>
        <nav aria-label="Tu cuenta">
            <h2 class="footer-title">Tu cuenta</h2>
            <ul>
                <?php if ($user !== null): ?>
                    <li><a href="<?= e(url('/cuenta')) ?>">Mi cuenta</a></li>
                    <li><a href="<?= e(url('/cuenta/pedidos')) ?>">Mis pedidos</a></li>
                <?php else: ?>
                    <li><a href="<?= e(url('/ingresar')) ?>">Ingresar</a></li>
                    <li><a href="<?= e(url('/registro')) ?>">Crear cuenta</a></li>
                    <li><a href="<?= e(url('/recuperar')) ?>">Recuperar contraseña</a></li>
                <?php endif; ?>
                <li><a href="<?= e(url('/carrito')) ?>">Carrito</a></li>
            </ul>
        </nav>
        <div class="footer-note">
            <h2 class="footer-title">Sobre esta tienda</h2>
            <p>Proyecto de demostración con nombre provisional. Las marcas, los precios y las existencias del catálogo inicial son ficticios. No vendemos animales vivos.</p>
        </div>
    </div>
    <div class="container footer-bottom">
        <p>© <?= e(date('Y')) ?> <?= e(config('app.name')) ?>. Iconos de Lucide (ISC) y Tabler Icons (MIT).</p>
    </div>
</footer>
