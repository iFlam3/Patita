<?php
/** @var string $active */
$links = [
    'panel'      => ['/admin', 'Resumen', 'store'],
    'productos'  => ['/admin/productos', 'Productos', 'package'],
    'pedidos'    => ['/admin/pedidos', 'Pedidos', 'truck'],
    'importador' => ['/admin/importador', 'Importador', 'leaf'],
];
?>
<nav class="admin-nav" aria-label="Administración">
    <ul>
        <?php foreach ($links as $key => [$path, $label, $iconName]): ?>
            <li><a href="<?= e(url($path)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= icon($iconName, 'icon icon-sm') ?> <?= e($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
