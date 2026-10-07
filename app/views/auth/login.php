<?php
/** @var array<string, string> $errors */
?>
<div class="container page auth-page">
    <section class="auth-card" aria-labelledby="login-titulo">
        <h1 id="login-titulo">Ingresar</h1>
        <p class="auth-intro">Entra para confirmar pedidos y ver tu historial.</p>
        <form action="<?= e(url('/ingresar')) ?>" method="post" novalidate data-once>
            <?= csrf_field() ?>
            <div class="field">
                <label for="l-email">Correo</label>
                <input id="l-email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e(old('email')) ?>"<?= field_attrs('email', $errors) ?>>
                <?= field_error('email', $errors) ?>
            </div>
            <div class="field">
                <label for="l-password">Contraseña</label>
                <div class="password-field">
                    <input id="l-password" name="password" type="password" autocomplete="current-password" required<?= field_attrs('password', $errors) ?>>
                    <button type="button" class="password-toggle" data-password-toggle="l-password" aria-pressed="false">Mostrar</button>
                </div>
                <?= field_error('password', $errors) ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Ingresando…">Ingresar</button>
        </form>
        <p class="auth-links">
            <a href="<?= e(url('/recuperar')) ?>">¿Olvidaste tu contraseña?</a>
        </p>
        <p class="auth-switch">¿No tienes cuenta? <a href="<?= e(url('/registro')) ?>">Crea una</a></p>
    </section>
</div>
