<?php
/**
 * Barras horizontales (SVG, porque la CSP bloquea style en línea); cada barra lleva su valor en texto.
 *
 * @var array<string, float> $items Etiqueta => valor en dinero.
 * @var string               $label Nombre accesible de la lista.
 */
$max = max(1.0, ...array_values($items ?: [0.0]));
?>
<?php if ($items === []): ?>
    <p class="field-hint">Sin ventas en este periodo.</p>
<?php else: ?>
    <ul class="bars" aria-label="<?= e($label) ?>">
        <?php foreach ($items as $name => $value): ?>
            <li class="bar">
                <span class="bar-label"><?= e((string) $name) ?></span>
                <svg class="bar-track" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true"><rect class="bar-fill" width="<?= round($value / $max * 100, 1) ?>" height="10" rx="5"/></svg>
                <span class="bar-value"><?= e('$' . number_format($value, 2)) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
