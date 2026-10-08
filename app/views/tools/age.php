<?php
/**
 * @var string                                  $species
 * @var string                                  $size
 * @var string                                  $years
 * @var string                                  $months
 * @var array{pet: float, human: float}|null    $result
 * @var string|null                             $error
 */
$sizes = (array) config('tools.dog_sizes');
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Herramientas', '/herramientas'], ['Calculadora de edad', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Calculadora de edad</h1>
            <p class="page-intro">¿Cuántos años humanos tiene tu perro o tu gato? Los dos primeros años maduran muy rápido y, en perros, el tamaño cambia el ritmo de envejecimiento.</p>
        </div>
    </header>

    <form class="panel tool-form" action="<?= e(url('/herramientas/edad')) ?>#resultado" method="get">
        <fieldset class="field-group">
            <legend>Mascota</legend>
            <div class="segmented">
                <label><input type="radio" name="especie" value="perro"<?= $species === 'perro' ? ' checked' : '' ?>> <?= icon('dog', 'icon icon-sm') ?> Perro</label>
                <label><input type="radio" name="especie" value="gato"<?= $species === 'gato' ? ' checked' : '' ?>> <?= icon('cat', 'icon icon-sm') ?> Gato</label>
            </div>
        </fieldset>
        <div class="field">
            <label for="t-tamano">Tamaño adulto (solo perros)</label>
            <select id="t-tamano" name="tamano">
                <?php foreach ($sizes as $key => $info): ?>
                    <option value="<?= e($key) ?>"<?= $size === $key ? ' selected' : '' ?>><?= e($info['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="t-anios">Años</label>
                <input id="t-anios" name="anios" type="number" min="0" max="30" step="1" inputmode="numeric" value="<?= e($years) ?>">
            </div>
            <div class="field">
                <label for="t-meses">Meses</label>
                <input id="t-meses" name="meses" type="number" min="0" max="11" step="1" inputmode="numeric" value="<?= e($months) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Calcular edad</button>
    </form>

    <div id="resultado">
        <?php if ($error !== null): ?>
            <p class="inline-notice inline-notice-warn" role="alert"><?= icon('circle-alert', 'icon icon-sm') ?> <?= e($error) ?></p>
        <?php elseif ($result !== null): ?>
            <section class="tool-result" role="status">
                <p class="tool-result-label">Equivale aproximadamente a</p>
                <p class="tool-result-value"><?= e(number_format($result['human'], 0)) ?> años humanos</p>
                <p>Para un <?= $species === 'perro' ? 'perro de tamaño ' . e(mb_strtolower(explode(' (', (string) $sizes[$size]['label'])[0], 'UTF-8')) : 'gato' ?> de <?= e(rtrim(rtrim(number_format($result['pet'], 1), '0'), '.')) ?> año(s).</p>
            </section>
        <?php endif; ?>
    </div>

    <p class="field-hint">Cálculo: el primer año equivale a unos 15 años humanos y el segundo a 9 más. Desde el tercero se suman 4 años por año en gatos y entre 4 y 7 en perros, según el tamaño. Es una estimación orientativa basada en tablas de equivalencia de uso común; no existe una fórmula exacta.</p>
</div>
