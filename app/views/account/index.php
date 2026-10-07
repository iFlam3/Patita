<?php
/**
 * @var array<string, mixed>       $user
 * @var list<array<string, mixed>> $orders
 * @var array<string, string>      $errors
 */
?>
<div class="container page">
    <header class="page-head">
        <div><h1>Mi cuenta</h1></div>
    </header>
    <div class="account-layout">
        <?= App\Core\View::partial('partials/account-nav', ['active' => 'perfil']) ?>

        <div class="account-main">
            <section class="panel" id="perfil" aria-labelledby="perfil-titulo">
                <h2 id="perfil-titulo">Perfil</h2>
                <form action="<?= e(url('/cuenta/perfil')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="field-row">
                        <div class="field">
                            <label for="p-name">Nombre</label>
                            <input id="p-name" name="name" type="text" autocomplete="name" required maxlength="120" value="<?= e(old('name', (string) $user['name'])) ?>"<?= field_attrs('name', $errors) ?>>
                            <?= field_error('name', $errors) ?>
                        </div>
                        <div class="field">
                            <label for="p-email">Correo</label>
                            <input id="p-email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e(old('email', (string) $user['email'])) ?>"<?= field_attrs('email', $errors) ?>>
                            <?= field_error('email', $errors) ?>
                        </div>
                    </div>
                    <div class="field field-narrow">
                        <label for="p-current">Contraseña actual <span class="optional">(solo si cambias el correo)</span></label>
                        <input id="p-current" name="confirm_password" type="password" autocomplete="current-password"<?= field_attrs('confirm_password', $errors) ?>>
                        <?= field_error('confirm_password', $errors) ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar perfil</button>
                </form>
            </section>

            <section class="panel" id="contrasena" aria-labelledby="contrasena-titulo">
                <h2 id="contrasena-titulo">Cambiar contraseña</h2>
                <p class="field-hint">Al cambiarla cerramos tus sesiones abiertas en otros dispositivos.</p>
                <form action="<?= e(url('/cuenta/contrasena')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="field field-narrow">
                        <label for="pw-current">Contraseña actual</label>
                        <input id="pw-current" name="current_password" type="password" autocomplete="current-password" required<?= field_attrs('current_password', $errors) ?>>
                        <?= field_error('current_password', $errors) ?>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="pw-new">Contraseña nueva</label>
                            <input id="pw-new" name="password" type="password" autocomplete="new-password" required<?= field_attrs('password', $errors) ?>>
                            <?= field_error('password', $errors) ?>
                        </div>
                        <div class="field">
                            <label for="pw-new2">Repite la contraseña nueva</label>
                            <input id="pw-new2" name="password_confirmation" type="password" autocomplete="new-password" required<?= field_attrs('password_confirmation', $errors) ?>>
                            <?= field_error('password_confirmation', $errors) ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                </form>
            </section>

            <section class="panel" aria-labelledby="recientes-titulo">
                <div class="review-block-head">
                    <h2 id="recientes-titulo">Pedidos recientes</h2>
                    <?php if ($orders !== []): ?><a href="<?= e(url('/cuenta/pedidos')) ?>">Ver todos</a><?php endif; ?>
                </div>
                <?php if ($orders === []): ?>
                    <p>Todavía no tienes pedidos. <a href="<?= e(url('/catalogo')) ?>">Explora el catálogo</a>.</p>
                <?php else: ?>
                    <?= App\Core\View::partial('account/orders-table', ['orders' => $orders]) ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
