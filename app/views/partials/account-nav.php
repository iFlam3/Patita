<?php
/** @var string $active */
$user = auth_user();
$links = [
    'perfil'  => ['/cuenta', 'Perfil y contraseña', 'user'],
    'pedidos' => ['/cuenta/pedidos', 'Mis pedidos', 'package'],
];
?>
<nav class="side-nav" aria-label="Mi cuenta">
    <p class="side-nav-user">
        <strong><?= e($user['name'] ?? '') ?></strong>
        <span><?= e($user['email'] ?? '') ?></span>
    </p>
    <ul>
        <?php foreach ($links as $key => [$path, $label, $iconName]): ?>
            <li><a href="<?= e(url($path)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= icon($iconName, 'icon icon-sm') ?> <?= e($label) ?></a></li>
        <?php endforeach; ?>
        <?php if (is_admin()): ?>
            <li><a href="<?= e(url('/admin')) ?>"><?= icon('store', 'icon icon-sm') ?> Administración</a></li>
        <?php endif; ?>
    </ul>
    <form action="<?= e(url('/salir')) ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-quiet btn-block"><?= icon('log-out', 'icon icon-sm') ?> Cerrar sesión</button>
    </form>
</nav>
