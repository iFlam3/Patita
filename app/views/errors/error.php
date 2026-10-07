<?php
/**
 * @var int    $status
 * @var string $message
 */
?>
<div class="container page error-page">
    <p class="error-code" aria-hidden="true"><?= (int) $status ?></p>
    <h1><?= e($title ?? 'Algo salió mal') ?></h1>
    <p class="error-message"><?= e($message) ?></p>
    <?php if ($status === 419): ?>
        <p>Por seguridad, los formularios vencen cuando la sesión se renueva. Recarga la página y vuelve a enviarlo.</p>
    <?php elseif ($status === 403): ?>
        <p>Si crees que deberías tener acceso, ingresa con una cuenta de administrador.</p>
    <?php endif; ?>
    <div class="state-actions">
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Ir al inicio</a>
        <a class="btn btn-quiet" href="<?= e(url('/catalogo')) ?>">Ver catálogo</a>
    </div>
</div>
