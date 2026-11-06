<?php
/**
 * Dona en SVG (stroke-dasharray sobre un círculo de circunferencia 100).
 *
 * @var list<array{label: string, value: int, class: string}> $segments
 * @var string                                                $caption
 */
$sum = array_sum(array_column($segments, 'value'));
$r = 15.915; // 2πr = 100
$offset = 25.0; // empieza arriba
?>
<div class="donut">
    <svg viewBox="0 0 42 42" role="img" aria-label="<?= e($caption) ?>">
        <circle class="donut-hole" cx="21" cy="21" r="<?= $r ?>"/>
        <?php foreach ($segments as $s): if ($sum === 0 || $s['value'] === 0) { continue; } $len = $s['value'] / $sum * 100; ?>
            <circle class="donut-seg <?= e($s['class']) ?>" cx="21" cy="21" r="<?= $r ?>" stroke-dasharray="<?= round($len, 3) ?> <?= round(100 - $len, 3) ?>" stroke-dashoffset="<?= round($offset, 3) ?>"><title><?= e($s['label'] . ': ' . $s['value']) ?></title></circle>
            <?php $offset -= $len; ?>
        <?php endforeach; ?>
        <text class="donut-total" x="21" y="21"><?= $sum ?></text>
        <text class="donut-sub" x="21" y="26.5">pedidos</text>
    </svg>
    <ul class="donut-legend">
        <?php foreach ($segments as $s): ?>
            <li><i class="key <?= e($s['class']) ?>"></i> <?= e($s['label']) ?> <strong><?= $s['value'] ?></strong></li>
        <?php endforeach; ?>
    </ul>
</div>
