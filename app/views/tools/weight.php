<?php
/**
 * @var string                                         $species
 * @var string                                         $weight
 * @var int                                            $condition
 * @var array<int, array{label: string, text: string}> $conditions
 * @var array<string, mixed>|null                      $result
 * @var string|null                                    $error
 */
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Herramientas', '/herramientas'], ['Peso saludable', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Calculadora de peso saludable</h1>
            <p class="page-intro">Como cada raza y cada animal es distinto, no usamos tablas de peso por raza: evaluamos la condición corporal de tu perro o gato, que es lo que revisan los veterinarios.</p>
        </div>
    </header>

    <form class="panel tool-form" action="<?= e(url('/herramientas/peso-saludable')) ?>#resultado" method="get">
        <fieldset class="field-group">
            <legend>Mascota</legend>
            <div class="segmented">
                <label><input type="radio" name="especie" value="perro"<?= $species === 'perro' ? ' checked' : '' ?>> <?= icon('dog', 'icon icon-sm') ?> Perro</label>
                <label><input type="radio" name="especie" value="gato"<?= $species === 'gato' ? ' checked' : '' ?>> <?= icon('cat', 'icon icon-sm') ?> Gato</label>
            </div>
        </fieldset>
        <div class="field field-narrow">
            <label for="t-peso">Peso actual (kg)</label>
            <input id="t-peso" name="peso" type="number" min="0.1" max="120" step="0.1" inputmode="decimal" value="<?= e($weight) ?>">
        </div>
        <fieldset class="field-group">
            <legend>¿Cómo se ve y se siente su cuerpo?</legend>
            <p class="field-hint">Pasa las manos por sus costillas y míralo desde arriba y de lado.</p>
            <div class="condition-list">
                <?php foreach ($conditions as $score => $info): ?>
                    <label class="radio-card">
                        <input type="radio" name="condicion" value="<?= (int) $score ?>"<?= $condition === $score ? ' checked' : '' ?>>
                        <span class="radio-card-body">
                            <span class="radio-card-title"><?= e($info['label']) ?></span>
                            <span class="radio-card-text"><?= e($info['text']) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <button type="submit" class="btn btn-primary">Calcular</button>
    </form>

    <div id="resultado">
        <?php if ($error !== null): ?>
            <p class="inline-notice inline-notice-warn" role="alert"><?= icon('circle-alert', 'icon icon-sm') ?> <?= e($error) ?></p>
        <?php elseif ($result !== null): ?>
            <?php if ($result['status'] === 'over'): ?>
                <section class="tool-result tool-result-warn" role="status">
                    <p class="tool-result-label">Peso de referencia aproximado</p>
                    <p class="tool-result-value"><?= e(number_format((float) $result['ideal'], 1)) ?> kg</p>
                    <p>Con la condición que elegiste, su peso actual (<?= e(number_format((float) $result['weight'], 1)) ?> kg) estaría cerca de un <?= (int) $result['excess_percent'] ?> % por encima de lo saludable. Habla con tu veterinario antes de cambiar su alimentación: bajar de peso debe ser gradual.</p>
                </section>
            <?php elseif ($result['status'] === 'under'): ?>
                <section class="tool-result tool-result-warn" role="status">
                    <p class="tool-result-label">Condición corporal</p>
                    <p class="tool-result-value">Por debajo de lo ideal</p>
                    <p>La delgadez puede tener muchas causas, así que no calculamos una cifra. Consulta a tu veterinario para revisar su alimentación y su salud.</p>
                </section>
            <?php else: ?>
                <section class="tool-result tool-result-ok" role="status">
                    <p class="tool-result-label">Condición corporal</p>
                    <p class="tool-result-value">Ideal</p>
                    <p>Su peso actual (<?= e(number_format((float) $result['weight'], 1)) ?> kg) parece adecuado. Mantén sus porciones y su actividad, y vuelve a revisarlo cada pocos meses.</p>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <p class="field-hint">Cálculo: se usa la escala de condición corporal de 9 puntos, de uso veterinario. Cada punto por encima del ideal (5) equivale aproximadamente a un 10 % de exceso de peso. Es una estimación orientativa; tu veterinario puede evaluar con precisión la condición corporal de tu mascota.</p>
</div>
