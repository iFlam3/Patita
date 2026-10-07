<?php
/**
 * @var array<string, mixed>                                      $summary
 * @var array<string, string>                                     $values
 * @var array<string, string>                                     $countries
 * @var array<string, array{label: string, description: string}>  $paymentMethods
 */
$errors = App\Core\Session::errors();
$currency = (string) $summary['currency'];
?>
<div class="container page">
    <?= App\Core\View::partial('partials/checkout-steps', ['current' => 2]) ?>
    <header class="page-head">
        <div>
            <h1>Datos de entrega</h1>
            <p class="page-intro">Antes de confirmar verás un resumen completo con el costo de envío.</p>
        </div>
    </header>

    <?php foreach ($summary['notices'] as $notice): ?>
        <p class="inline-notice inline-notice-warn" role="status"><?= icon('circle-alert', 'icon icon-sm') ?> <?= e($notice) ?></p>
    <?php endforeach; ?>

    <div class="checkout-layout">
        <form class="checkout-form" action="<?= e(url('/checkout/revisar')) ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <fieldset class="form-section">
                <legend>Contacto</legend>
                <div class="field">
                    <label for="c-name">Nombre completo</label>
                    <input id="c-name" name="name" type="text" autocomplete="name" required maxlength="120" value="<?= e($values['name']) ?>"<?= field_attrs('name', $errors) ?>>
                    <?= field_error('name', $errors) ?>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="c-email">Correo</label>
                        <input id="c-email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e($values['email']) ?>"<?= field_attrs('email', $errors) ?>>
                        <?= field_error('email', $errors) ?>
                    </div>
                    <div class="field">
                        <label for="c-phone">Teléfono</label>
                        <input id="c-phone" name="phone" type="tel" autocomplete="tel" required maxlength="25" value="<?= e($values['phone']) ?>"<?= field_attrs('phone', $errors, 'c-phone-hint') ?>>
                        <p class="field-hint" id="c-phone-hint">Con código de país si quieres, por ejemplo +52 55 1234 5678.</p>
                        <?= field_error('phone', $errors) ?>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Dirección de entrega</legend>
                <div class="field">
                    <label for="c-country">País</label>
                    <select id="c-country" name="country" autocomplete="country"<?= field_attrs('country', $errors) ?>>
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?= e($code) ?>"<?= $values['country'] === $code ? ' selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error('country', $errors) ?>
                </div>
                <div class="field">
                    <label for="c-line1">Calle y número</label>
                    <input id="c-line1" name="line1" type="text" autocomplete="address-line1" required maxlength="150" value="<?= e($values['line1']) ?>"<?= field_attrs('line1', $errors) ?>>
                    <?= field_error('line1', $errors) ?>
                </div>
                <div class="field">
                    <label for="c-line2">Departamento, interior, colonia o barrio <span class="optional">(opcional)</span></label>
                    <input id="c-line2" name="line2" type="text" autocomplete="address-line2" maxlength="100" value="<?= e($values['line2']) ?>"<?= field_attrs('line2', $errors) ?>>
                    <?= field_error('line2', $errors) ?>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="c-city">Ciudad o municipio</label>
                        <input id="c-city" name="city" type="text" autocomplete="address-level2" required maxlength="80" value="<?= e($values['city']) ?>"<?= field_attrs('city', $errors) ?>>
                        <?= field_error('city', $errors) ?>
                    </div>
                    <div class="field">
                        <label for="c-region">Estado, provincia o departamento <span class="optional">(opcional)</span></label>
                        <input id="c-region" name="region" type="text" autocomplete="address-level1" maxlength="80" value="<?= e($values['region']) ?>"<?= field_attrs('region', $errors) ?>>
                        <?= field_error('region', $errors) ?>
                    </div>
                </div>
                <div class="field field-narrow">
                    <label for="c-postal">Código postal <span class="optional">(opcional)</span></label>
                    <input id="c-postal" name="postal_code" type="text" autocomplete="postal-code" maxlength="12" value="<?= e($values['postal_code']) ?>"<?= field_attrs('postal_code', $errors) ?>>
                    <?= field_error('postal_code', $errors) ?>
                </div>
                <div class="field">
                    <label for="c-references">Referencias para la entrega <span class="optional">(opcional)</span></label>
                    <textarea id="c-references" name="references" rows="2" maxlength="250"<?= field_attrs('references', $errors) ?>><?= e($values['references']) ?></textarea>
                    <?= field_error('references', $errors) ?>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Forma de pago</legend>
                <p class="field-hint">Modalidad demostrativa: no se realiza ningún cobro y no pedimos datos de tarjeta.</p>
                <div class="radio-cards">
                    <?php foreach ($paymentMethods as $code => $method): ?>
                        <label class="radio-card">
                            <input type="radio" name="payment_method" value="<?= e($code) ?>"<?= $values['payment_method'] === $code ? ' checked' : '' ?>>
                            <span class="radio-card-body">
                                <span class="radio-card-title"><?= icon($code === 'bank_transfer' ? 'banknote' : 'hand-coins', 'icon icon-sm') ?> <?= e($method['label']) ?></span>
                                <span class="radio-card-text"><?= e($method['description']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= field_error('payment_method', $errors) ?>
            </fieldset>

            <div class="form-actions">
                <a class="link-back" href="<?= e(url('/carrito')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Volver al carrito</a>
                <button type="submit" class="btn btn-primary btn-lg">Revisar pedido</button>
            </div>
        </form>

        <aside class="summary-card" aria-labelledby="resumen-checkout">
            <h2 id="resumen-checkout">Tu pedido</h2>
            <ul class="mini-lines">
                <?php foreach ($summary['lines'] as $line): ?>
                    <li>
                        <span class="mini-lines-name"><?= e($line['product']['name']) ?> <span class="mini-lines-qty">×<?= (int) $line['quantity'] ?></span></span>
                        <?= price_tag($line['total_cents'], $currency) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?= App\Core\View::partial('partials/totals', [
                'subtotal' => $summary['subtotal_cents'],
                'shipping' => $summary['shipping_cents'],
                'total'    => $summary['total_cents'],
                'currency' => $currency,
            ]) ?>
        </aside>
    </div>
</div>
