<?php
/**
 * @var bool                  $valid
 * @var array<string, string> $errors
 */
$min = (int) config('app.auth.password_min_length', 8);
?>
<div class="container page auth-page">
    <section class="auth-card" aria-labelledby="restablecer-titulo">
        <h1 id="restablecer-titulo">Nueva contraseña</h1>
        <?php if (!$valid): ?>
            <div class="state state-error state-compact" role="alert">
                <?= icon('circle-alert', 'icon icon-lg') ?>
                <p>El enlace no es válido, ya se usó o venció. Los enlaces duran <?= (int) config('app.auth.reset_ttl_minutes', 60) ?> minutos y sirven una sola vez.</p>
                <a class="btn btn-primary" href="<?= e(url('/recuperar')) ?>">Pedir un enlace nuevo</a>
            </div>
        <?php else: ?>
            <p class="auth-intro">Elige una contraseña que no uses en otros sitios.</p>
            <form action="<?= e(url('/restablecer')) ?>" method="post" novalidate data-once>
                <?= csrf_field() ?>
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
        <?php endif; ?>
    </section>
</div>
