<?php
/**
 * Gráfica de líneas en SVG: ingresos reales, recta de regresión y proyección.
 *
 * @var string       $id
 * @var string       $caption
 * @var list<string> $labels
 * @var list<float>  $values
 * @var list<float>  $fitted
 * @var list<string> $futureLabels
 * @var list<float>  $forecast
 */
$w = 720; $h = 280; $left = 56; $right = 16; $top = 16; $bottom = 36;
$all = array_merge($values, $fitted, $forecast);
$total = count($values) + count($forecast);
$max = max(1.0, ...$all);
$magnitude = 10 ** floor(log10($max));
$max = ceil($max / $magnitude * 2) / 2 * $magnitude; // tope redondo para el eje
$x = static fn (int $i): float => $left + ($total > 1 ? $i / ($total - 1) : 0) * ($w - $left - $right);
$y = static fn (float $v): float => $top + (1 - $v / $max) * ($h - $top - $bottom);
$path = static function (array $vals, int $offset) use ($x, $y): string {
    $points = [];
    foreach (array_values($vals) as $i => $v) {
        $points[] = round($x($i + $offset), 1) . ',' . round($y($v), 1);
    }

    return implode(' ', $points);
};
$money = static fn (float $v): string => '$' . number_format($v, $v < 10 && $v > 0 ? 1 : 0);
$allLabels = array_merge($labels, $futureLabels);
$every = max(1, (int) ceil($total / 8));
$lastReal = count($values) - 1;
?>
<figure class="chart">
    <svg viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-labelledby="<?= e($id) ?>-t">
        <title id="<?= e($id) ?>-t"><?= e($caption) ?></title>
        <?php for ($g = 0; $g <= 4; $g++): $v = $max * $g / 4; ?>
            <line class="chart-grid" x1="<?= $left ?>" x2="<?= $w - $right ?>" y1="<?= round($y($v), 1) ?>" y2="<?= round($y($v), 1) ?>"/>
            <text class="chart-axis" x="<?= $left - 8 ?>" y="<?= round($y($v), 1) + 4 ?>" text-anchor="end"><?= e($money($v)) ?></text>
        <?php endfor; ?>
        <?php foreach ($allLabels as $i => $label): if ($i % $every !== 0 && $i !== $total - 1) { continue; } ?>
            <text class="chart-axis<?= $i > $lastReal ? ' chart-axis-future' : '' ?>" x="<?= round($x($i), 1) ?>" y="<?= $h - 12 ?>" text-anchor="middle"><?= e($label) ?></text>
        <?php endforeach; ?>
        <?php if ($forecast !== []): ?>
            <rect class="chart-future-band" x="<?= round($x($lastReal), 1) ?>" y="<?= $top ?>" width="<?= round($x($total - 1) - $x($lastReal), 1) ?>" height="<?= $h - $top - $bottom ?>"/>
        <?php endif; ?>
        <polygon class="chart-area" points="<?= round($x(0), 1) ?>,<?= round($y(0), 1) ?> <?= $path($values, 0) ?> <?= round($x($lastReal), 1) ?>,<?= round($y(0), 1) ?>"/>
        <polyline class="chart-line" points="<?= $path($values, 0) ?>"/>
        <polyline class="chart-fit" points="<?= $path($fitted, 0) ?>"/>
        <?php if ($forecast !== []): ?>
            <polyline class="chart-forecast" points="<?= $path([end($fitted) ?: 0.0], $lastReal) ?> <?= $path($forecast, $lastReal + 1) ?>"/>
            <?php foreach ($forecast as $i => $v): ?>
                <circle class="chart-forecast-dot" cx="<?= round($x($lastReal + 1 + $i), 1) ?>" cy="<?= round($y($v), 1) ?>" r="4"><title><?= e($futureLabels[$i] . ': ' . $money($v) . ' (proyección)') ?></title></circle>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php foreach ($values as $i => $v): ?>
            <circle class="chart-dot" cx="<?= round($x($i), 1) ?>" cy="<?= round($y($v), 1) ?>" r="<?= $total > 40 ? 2 : 3 ?>"><title><?= e($labels[$i] . ': ' . $money($v)) ?></title></circle>
        <?php endforeach; ?>
    </svg>
    <figcaption class="chart-legend">
        <span><i class="key key-line"></i> Ingresos reales</span>
        <span><i class="key key-fit"></i> Tendencia (regresión lineal)</span>
        <span><i class="key key-forecast"></i> Proyección</span>
    </figcaption>
    <details class="chart-data">
        <summary>Ver datos</summary>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th scope="col">Periodo</th><th scope="col" class="num">Ingresos</th><th scope="col" class="num">Tendencia</th></tr></thead>
                <tbody>
                    <?php foreach ($values as $i => $v): ?>
                        <tr><th scope="row"><?= e($labels[$i]) ?></th><td class="num"><?= e('$' . number_format($v, 2)) ?></td><td class="num"><?= e('$' . number_format($fitted[$i], 2)) ?></td></tr>
                    <?php endforeach; ?>
                    <?php foreach ($forecast as $i => $v): ?>
                        <tr><th scope="row"><?= e($futureLabels[$i]) ?> (proyección)</th><td class="num">—</td><td class="num"><?= e('$' . number_format($v, 2)) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
</figure>
