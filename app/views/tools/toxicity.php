<?php
/**
 * @var string                            $pet
 * @var list<array<string, string>>       $rows
 */
$petName = mb_strtolower(pet_label($pet), 'UTF-8');
$counts = array_count_values(array_column($rows, 'level'));
$known = array_values(array_filter($rows, static fn (array $r): bool => $r['level'] !== 'nodata'));
$unknown = array_column(array_filter($rows, static fn (array $r): bool => $r['level'] === 'nodata'), 'food');
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Herramientas', '/herramientas'], ['Alimentos peligrosos', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Alimentos peligrosos para mascotas</h1>
            <p class="page-intro">Elige tu mascota para ver qué alimentos de uso común son tóxicos o no recomendados.</p>
        </div>
    </header>

    <p class="emergency"><?= icon('circle-alert') ?> <span><strong>En caso de ingestión</strong>, acude de inmediato a un veterinario o a un centro de toxicología animal, aunque no haya síntomas.</span></p>

    <nav class="category-chips" aria-label="Elige tu mascota">
        <ul>
            <?php foreach (pets() as $code => $info): ?>
                <li>
                    <a class="chip-link<?= $pet === $code ? ' is-active' : '' ?>" href="<?= e(url('/herramientas/alimentos-peligrosos', ['mascota' => $code])) ?>#tabla-alimentos"<?= $pet === $code ? ' aria-current="true"' : '' ?>>
                        <?= icon((string) $info['icon'], 'icon icon-sm') ?> <?= e($info['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <section class="panel" aria-labelledby="tabla-alimentos">
        <h2 id="tabla-alimentos"><?= icon((string) pets()[$pet]['icon'], 'icon icon-sm') ?> Alimentos para <?= e($petName) ?></h2>
        <?php if ($pet === 'fish'): ?>
            <p class="emergency"><?= icon('info') ?> <span>Los peces deben comer solo alimento formulado para su especie (escamas, gránulos, congelados o vivos). No les ofrezcas comida humana: además de no ser apta, los restos contaminan el agua y elevan el amoníaco.</span></p>
        <?php endif; ?>
        <?php if ($known !== []): ?>
        <p class="field-hint">
            <?= (int) ($counts['toxic'] ?? 0) ?> tóxicos y <?= (int) ($counts['avoid'] ?? 0) ?> no recomendados para esta especie.
        </p>
        <div class="table-wrap">
            <table class="table tox-table">
                <thead>
                    <tr>
                        <th scope="col">Alimento</th>
                        <th scope="col">Riesgo</th>
                        <th scope="col">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($known as $row): ?>
                        <tr>
                            <th scope="row"><?= e($row['food']) ?></th>
                            <td><span class="tox-badge tox-<?= e($row['class']) ?>"><?= e($row['label']) ?></span></td>
                            <td><?= e($row['note']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php if ($unknown !== []): ?>
            <p class="field-hint"><strong>Sin datos verificados para esta especie:</strong> <?= e(implode(', ', $unknown)) ?>. Que no aparezcan en la tabla no significa que sean seguros: no los ofrezcas sin consultar a un veterinario.</p>
        <?php endif; ?>
    </section>

    <p class="field-hint">Basado en las listas públicas de alimentos peligrosos para mascotas de la ASPCA (Animal Poison Control Center) y fuentes veterinarias de divulgación. La lista no es exhaustiva: muchos otros alimentos, plantas y productos del hogar también son peligrosos.</p>
</div>
