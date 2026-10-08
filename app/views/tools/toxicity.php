<?php
/**
 * @var string                    $pet
 * @var string                    $food
 * @var array<string, string>|null $result
 */
$foods = (array) config('tools.foods');
$levels = (array) config('tools.levels');
?>
<div class="container page page-narrow">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Herramientas', '/herramientas'], ['Alimentos peligrosos', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Alimentos peligrosos para mascotas</h1>
            <p class="page-intro">Consulta el nivel de riesgo de alimentos de uso común según la especie.</p>
        </div>
    </header>

    <p class="emergency"><?= icon('circle-alert') ?> <span><strong>En caso de ingestión</strong>, acude de inmediato a un veterinario o a un centro de toxicología animal, aunque no haya síntomas.</span></p>

    <form class="panel tool-form" action="<?= e(url('/herramientas/alimentos-peligrosos')) ?>#resultado" method="get">
        <div class="field-row">
            <div class="field">
                <label for="t-mascota">Mascota</label>
                <select id="t-mascota" name="mascota" required>
                    <option value="">Elige una mascota</option>
                    <?php foreach (pets() as $code => $info): ?>
                        <option value="<?= e($code) ?>"<?= $pet === $code ? ' selected' : '' ?>><?= e($info['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="t-alimento">Alimento</label>
                <select id="t-alimento" name="alimento" required>
                    <option value="">Elige un alimento</option>
                    <?php foreach ($foods as $key => $info): ?>
                        <option value="<?= e($key) ?>"<?= $food === $key ? ' selected' : '' ?>><?= e($info['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Consultar</button>
    </form>

    <div id="resultado">
        <?php if ($result !== null): ?>
            <section class="tool-result tool-result-<?= e($result['class']) ?>" role="status">
                <p class="tool-result-label"><?= e($result['food']) ?> para <?= e(mb_strtolower(pet_label($pet), 'UTF-8')) ?></p>
                <p class="tool-result-value"><?= e($result['label']) ?></p>
                <p><?= e($result['note']) ?></p>
                <?php if ($result['level'] === 'nodata'): ?>
                    <p>La ausencia de datos no implica que sea seguro. No se recomienda ofrecerlo sin consultar a un veterinario.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <?php if ($pet !== ''): ?>
        <section class="panel" aria-labelledby="tabla-alimentos">
            <h2 id="tabla-alimentos">Todos los alimentos para <?= e(mb_strtolower(pet_label($pet), 'UTF-8')) ?></h2>
            <ul class="tox-list">
                <?php foreach ($foods as $key => $info): ?>
                    <?php $level = (string) ($info['levels'][$pet] ?? 'nodata'); ?>
                    <li>
                        <span><?= e($info['label']) ?></span>
                        <span class="tox-badge tox-<?= e($levels[$level]['class']) ?>"><?= e($levels[$level]['short']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <p class="field-hint">Basado en las listas públicas de alimentos peligrosos para mascotas de la ASPCA (Animal Poison Control Center) y fuentes veterinarias de divulgación. La lista no es exhaustiva: muchos otros alimentos, plantas y productos del hogar también son peligrosos.</p>
</div>
