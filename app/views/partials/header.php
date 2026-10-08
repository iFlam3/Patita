<?php
$user = auth_user();
$cartCount = App\Services\Cart::count();
?>
<header class="site-header">
    <div class="container header-main">
        <a class="brand" href="<?= e(url('/')) ?>">
            <img class="brand-mark" src="<?= e(asset('assets/images/logo.svg')) ?>" alt="" width="40" height="40">
            <span class="brand-name"><?= e(config('app.name')) ?></span>
        </a>

        <form class="header-search" role="search" action="<?= e(url('/catalogo')) ?>" method="get">
            <label class="sr-only" for="buscador">Buscar productos</label>
            <input id="buscador" type="search" name="q" maxlength="100" placeholder="Busca alimento, correas, acuarios…" autocomplete="off">
            <button type="submit" class="header-search-button">
                <?= icon('search') ?><span class="sr-only">Buscar</span>
            </button>
        </form>

        <nav class="header-actions" aria-label="Cuenta y carrito">
            <a class="header-link" href="<?= e(url($user !== null ? '/cuenta' : '/ingresar')) ?>">
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
    </div>

    <nav class="main-nav" aria-label="Principal">
        <div class="container main-nav-inner">
            <details class="nav-dropdown" data-dropdown>
                <summary>Comprar por mascota <?= icon('chevron-down', 'icon icon-sm') ?></summary>
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
            <a href="<?= e(url('/marcas')) ?>">Comprar por marca</a>
            <a href="<?= e(url('/catalogo')) ?>">Catálogo</a>
            <a href="<?= e(url('/cuidados')) ?>">Cuidados</a>
            <a href="<?= e(url('/herramientas')) ?>">Herramientas</a>
            <a class="main-nav-secondary" href="<?= e(url('/envios-y-pagos')) ?>">Envíos y pagos</a>
            <?php if ($user !== null && $user['role'] === 'admin'): ?>
                <a class="main-nav-admin" href="<?= e(url('/admin')) ?>">Administración</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
