<?php
/**
 * @var int    $status
 * @var string $message
 */
?>
<div class="container page error-page<?= $status === 404 ? ' error-page-404' : '' ?>">
    <?php if ($status === 404): ?>
        <img class="error-dog" src="<?= e(asset('assets/images/photos/404-dog.jpg')) ?>" alt="Cachorro blanco de orejas enormes mirando a la cámara con cara de no saber nada" width="339" height="513" decoding="async">
    <?php endif; ?>
    <p class="error-code" aria-hidden="true"><?= (int) $status ?></p>
    <?php if ($status === 404): ?>
        <h1>Con estas orejas y ni así la encontré</h1>
        <p class="error-message">Busqué por todos lados. Debajo del sofá, en el patio, hasta en el plato de comida (dos veces, por si acaso). Esta página no existe.</p>
    <?php else: ?>
        <h1><?= e($title ?? 'Algo salió mal') ?></h1>
        <p class="error-message"><?= e($message) ?></p>
    <?php endif; ?>
    <?php if ($status === 419): ?>
        <p>Por seguridad, los formularios vencen cuando la sesión se renueva. Recarga la página y vuelve a enviarlo.</p>
    <?php elseif ($status === 404): ?>
        <p>Seguro escribiste mal el enlace. No te juzgo, yo tampoco sé leer. Prueba con el buscador:</p>
        <form class="header-search error-search" role="search" action="<?= e(url('/catalogo')) ?>" method="get">
            <label class="sr-only" for="buscador-404">Buscar productos</label>
            <input id="buscador-404" type="search" name="q" maxlength="100" placeholder="Busca alimento, correas, acuarios…">
            <button type="submit" class="header-search-button"><?= icon('search') ?><span class="sr-only">Buscar</span></button>
        </form>
        <nav class="category-chips error-chips" aria-label="Comprar por mascota">
            <ul>
                <?php foreach (pets() as $code => $info): ?>
                    <li><a class="chip-link" href="<?= e(url('/mascotas/' . pet_slug($code))) ?>"><?= icon((string) $info['icon'], 'icon icon-sm') ?> <?= e($info['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php elseif ($status === 403): ?>
        <p>Si crees que deberías tener acceso, ingresa con una cuenta de administrador.</p>
    <?php endif; ?>
    <div class="state-actions">
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Ir al inicio</a>
        <a class="btn btn-quiet" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
    </div>
</div>
