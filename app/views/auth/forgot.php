<?php
/** @var array<string, string> $errors */
?>
<div class="container page auth-page">
    <section class="auth-card" aria-labelledby="recuperar-titulo">
        <h1 id="recuperar-titulo">Recuperar contraseña</h1>
        <p class="auth-intro">Escribe el correo de tu cuenta. Si existe, te enviaremos un enlace para crear una contraseña nueva.</p>
        <form action="<?= e(url('/recuperar')) ?>" method="post" novalidate data-once>
            <?= csrf_field() ?>
            <div class="field">
                <label for="f-email">Correo</label>
                <input id="f-email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e(old('email')) ?>"<?= field_attrs('email', $errors) ?>>
                <?= field_error('email', $errors) ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Enviando…">Enviar enlace</button>
        </form>
        <p class="auth-switch"><a href="<?= e(url('/ingresar')) ?>">Volver a ingresar</a></p>
    </section>
</div>
