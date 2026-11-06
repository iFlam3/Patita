<?php
/**
 * @var string                $email
 * @var array<string, string> $errors
 */
$min = (int) config('app.auth.password_min_length', 8);
?>
<div class="container page auth-page">
    <section class="auth-card" aria-labelledby="restablecer-titulo">
        <h1 id="restablecer-titulo">Nueva contraseña</h1>
        <p class="auth-intro">Escribe el código de 6 dígitos que enviamos a <strong><?= e($email) ?></strong> y elige una contraseña que no uses en otros sitios.</p>
        <form action="<?= e(url('/restablecer')) ?>" method="post" novalidate data-once>
            <?= csrf_field() ?>
            <div class="field">
                <label for="n-code">Código de verificación</label>
                <input id="n-code" class="code-input" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="7" required placeholder="000000" value="<?= e(old('code')) ?>"<?= field_attrs('code', $errors, 'n-code-hint') ?>>
                <p class="field-hint" id="n-code-hint">Vence en <?= (int) config('app.auth.reset_ttl_minutes') ?> minutos. Tienes 5 intentos.</p>
                <?= field_error('code', $errors) ?>
            </div>
            <div class="field">
                <label for="n-password">Contraseña nueva</label>
                <div class="password-field">
                    <input id="n-password" name="password" type="password" autocomplete="new-password" required minlength="<?= $min ?>"<?= field_attrs('password', $errors, 'n-password-hint') ?>>
                    <button type="button" class="password-toggle" data-password-toggle="n-password" aria-pressed="false">Mostrar</button>
                </div>
                <p class="field-hint" id="n-password-hint">Al menos <?= $min ?> caracteres.</p>
                <?= field_error('password', $errors) ?>
            </div>
            <div class="field">
                <label for="n-password2">Repite la contraseña</label>
                <input id="n-password2" name="password_confirmation" type="password" autocomplete="new-password" required<?= field_attrs('password_confirmation', $errors) ?>>
                <?= field_error('password_confirmation', $errors) ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Guardando…">Guardar contraseña</button>
        </form>

        <form class="auth-resend" action="<?= e(url('/recuperar')) ?>" method="post" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="email" value="<?= e($email) ?>">
            <span>¿No te llegó?</span>
            <button type="submit" class="btn-link" data-loading-text="Enviando…">Reenviar código</button>
            <span aria-hidden="true">·</span>
            <a href="<?= e(url('/recuperar')) ?>">Usar otro correo</a>
        </form>
    </section>
</div>
