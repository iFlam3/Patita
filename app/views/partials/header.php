<?php
$user = auth_user();
$cartCount = App\Services\Cart::count();
$isAdmin = $user !== null && $user['role'] === 'admin';
$navLinks = [
    ['/marcas', 'Marcas'],
    ['/catalogo', 'Catálogo'],
    ['/cuidados', 'Cuidados'],
    ['/herramientas', 'Herramientas'],
    ['/envios-y-pagos', 'Envíos y pagos'],
];
?>
<header class="site-header">
    <div class="container header-bar">
        <a class="brand" href="<?= e(url('/')) ?>">
            <img class="brand-mark" src="<?= e(asset('assets/images/logo.svg')) ?>" alt="" width="40" height="40">
            <span class="brand-name"><?= e(config('app.name')) ?></span>
        </a>

        <nav class="main-nav" aria-label="Principal">
            <details class="nav-dropdown" data-dropdown>
                <summary>Mascotas <?= icon('chevron-down', 'icon icon-sm') ?></summary>
                <ul class="nav-dropdown-panel">
                    <?php foreach (pets() as $code => $pet): ?>
                        <li>
                            <a href="<?= e(url('/mascotas/' . $pet['slug'])) ?>">
                                <?= icon((string) $pet['icon']) ?><span><?= e($pet['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="nav-dropdown-all"><a href="<?= e(url('/mascotas')) ?>">Ver todas las mascotas</a></li>
                </ul>
            </details>
            <?php foreach ($navLinks as [$href, $label]): ?>
                <a href="<?= e(url($href)) ?>"<?= $href === '/envios-y-pagos' ? ' class="main-nav-extra"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
            <?php if ($isAdmin): ?>
                <a class="main-nav-admin" href="<?= e(url('/admin')) ?>">Administración</a>
            <?php endif; ?>
        </nav>

        <form class="header-search" role="search" action="<?= e(url('/catalogo')) ?>" method="get">
            <label class="sr-only" for="buscador">Buscar productos</label>
            <input id="buscador" type="search" name="q" maxlength="100" placeholder="Buscar productos" autocomplete="off">
            <button type="submit" class="header-search-button">
                <?= icon('search') ?><span class="sr-only">Buscar</span>
            </button>
        </form>

        <nav class="header-actions" aria-label="Cuenta y carrito">
            <a class="header-link header-account" href="<?= e(url($user !== null ? '/cuenta' : '/ingresar')) ?>">
                <?= icon('user') ?>
                <span class="header-link-label"><?= $user !== null ? 'Mi cuenta' : 'Ingresar' ?></span>
            </a>
            <a class="header-link header-cart" href="<?= e(url('/carrito')) ?>">
                <?= icon('shopping-bag') ?>
                <span class="header-link-label">Carrito</span>
                <span class="cart-count<?= $cartCount === 0 ? ' is-empty' : '' ?>" data-cart-count>
                    <span data-cart-count-value><?= $cartCount ?></span><span class="sr-only"> productos en el carrito</span>
                </span>
            </a>
        </nav>

        <details class="nav-menu" data-dropdown>
            <summary class="nav-menu-toggle">
                <?= icon('menu', 'icon nav-menu-open') ?><?= icon('x', 'icon nav-menu-close') ?><span class="sr-only">Menú</span>
            </summary>
            <nav class="nav-menu-panel" aria-label="Menú">
                <p class="nav-menu-title">Mascotas</p>
                <ul class="nav-menu-pets">
                    <?php foreach (pets() as $code => $pet): ?>
                        <li>
                            <a href="<?= e(url('/mascotas/' . $pet['slug'])) ?>">
                                <?= icon((string) $pet['icon']) ?><span><?= e($pet['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <ul class="nav-menu-links">
                    <?php foreach ($navLinks as [$href, $label]): ?>
                        <li><a href="<?= e(url($href)) ?>"><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= e(url($user !== null ? '/cuenta' : '/ingresar')) ?>"><?= $user !== null ? 'Mi cuenta' : 'Ingresar' ?></a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="<?= e(url('/admin')) ?>">Administración</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </details>
    </div>
</header>
