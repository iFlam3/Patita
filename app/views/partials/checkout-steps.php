<?php
/**
 * Indicador de pasos del checkout (es una secuencia real).
 *
 * @var int $current 1 a 4
 */
$steps = ['Carrito', 'Datos de entrega', 'Revisión', 'Confirmación'];
?>
<ol class="steps" aria-label="Pasos de la compra">
    <?php foreach ($steps as $i => $label): ?>
        <?php $n = $i + 1; ?>
        <li class="step<?= $n < $current ? ' is-done' : '' ?><?= $n === $current ? ' is-current' : '' ?>"<?= $n === $current ? ' aria-current="step"' : '' ?>>
            <span class="step-number"><?= $n < $current ? icon('check', 'icon icon-sm') : $n ?></span>
            <span class="step-label"><?= e($label) ?></span>
        </li>
    <?php endforeach; ?>
</ol>
