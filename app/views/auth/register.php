<?php
/** @var array<string, string> $errors */
$min = (int) config('app.auth.password_min_length', 8);
?>
<div class="container page auth-page">
    <section class="auth-card" aria-labelledby="registro-titulo">
        <h1 id="registro-titulo">Crear cuenta</h1>
        <p class="auth-intro">Solo necesitas tu nombre, tu correo y una contraseña.</p>
        <form action="<?= e(url('/registro')) ?>" method="post" novalidate data-once>
            <?= csrf_field() ?>
            <div class="field">
                <label for="r-name">Nombre</label>
                <input id="r-name" name="name" type="text" autocomplete="name" required maxlength="120" value="<?= e(old('name')) ?>"<?= field_attrs('name', $errors) ?>>
                <?= field_error('name', $errors) ?>
            </div>
            <div class="field">
                <label for="r-email">Correo</label>
                <input id="r-email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e(old('email')) ?>"<?= field_attrs('email', $errors) ?>>
                <?= field_error('email', $errors) ?>
            </div>
            <div class="field">
                <label for="r-password">Contraseña</label>
                <div class="password-field">
                    <input id="r-password" name="password" type="password" autocomplete="new-password" required minlength="<?= $min ?>"<?= field_attrs('password', $errors, 'r-password-hint') ?>>
                    <button type="button" class="password-toggle" data-password-toggle="r-password" aria-pressed="false">Mostrar</button>
                </div>
                <p class="field-hint" id="r-password-hint">Al menos <?= $min ?> caracteres. Una frase fácil de recordar funciona bien.</p>
                <?= field_error('password', $errors) ?>
            </div>
            <div class="field">
                <label for="r-password2">Repite la contraseña</label>
                <input id="r-password2" name="password_confirmation" type="password" autocomplete="new-password" required<?= field_attrs('password_confirmation', $errors) ?>>
                <?= field_error('password_confirmation', $errors) ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Creando cuenta…">Crear cuenta</button>
        </form>
        <p class="auth-switch">¿Ya tienes cuenta? <a href="<?= e(url('/ingresar')) ?>">Ingresa</a></p>
    </section>
</div>
