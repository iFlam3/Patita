<?php
/**
 * @var string               $code
 * @var array<string, mixed> $pet
 * @var array<string, mixed> $guide
 */
$guides = (array) config('catalog.pet_guides.' . $code, []);
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Guías de cuidado', '/cuidados'], [(string) $pet['label'], null]]]) ?>
    <header class="page-head page-head-pet">
        <span class="page-head-icon"><?= icon((string) $pet['icon']) ?></span>
        <div>
            <h1>Cuidado de <?= e(mb_strtolower((string) $pet['label'], 'UTF-8')) ?></h1>
            <p class="page-intro"><?= e((string) $guide['summary']) ?></p>
        </div>
    </header>

    <div class="care-layout">
        <div class="care-sections">
            <?php foreach ((array) $guide['sections'] as $i => $section): ?>
                <section class="care-section" aria-labelledby="cuidado-<?= (int) $i ?>">
                    <h2 id="cuidado-<?= (int) $i ?>"><span class="pet-guide-icon"><?= icon((string) $section['icon']) ?></span> <?= e((string) $section['title']) ?></h2>
                    <ul class="care-list">
                        <?php foreach ((array) $section['items'] as $item): ?>
                            <li><?= icon('check', 'icon icon-sm') ?><span><?= e((string) $item) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
            <p class="origin-note"><?= icon('info', 'icon icon-sm') ?> Información general de divulgación. No reemplaza la consulta veterinaria: ante cualquier duda de salud, alimentación o comportamiento, consulta a un profesional.</p>
        </div>

        <aside class="care-aside">
            <div class="summary-card">
                <h2>Productos para <?= e(mb_strtolower((string) $pet['label'], 'UTF-8')) ?></h2>
                <ul class="care-links">
                    <?php foreach ($guides as $module): ?>
                        <?php $cat = categories()[$module['category']] ?? null; ?>
                        <?php if ($cat !== null): ?>
                            <li><a href="<?= e(url('/mascotas/' . $pet['slug'], ['categoria' => $cat['slug']])) ?>"><?= icon($cat['icon'], 'icon icon-sm') ?> <?= e($cat['label']) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn-primary btn-block" href="<?= e(url('/mascotas/' . $pet['slug'])) ?>">Ver todo para <?= e(mb_strtolower((string) $pet['label'], 'UTF-8')) ?></a>
            </div>
            <div class="summary-card">
                <h2>Herramientas</h2>
                <ul class="care-links">
                    <?php if (in_array($code, ['dog', 'cat'], true)): ?>
                        <li><a href="<?= e(url('/herramientas/edad', ['especie' => $code === 'dog' ? 'perro' : 'gato'])) ?>"><?= icon('cake', 'icon icon-sm') ?> Edad en años humanos</a></li>
                        <li><a href="<?= e(url('/herramientas/peso-saludable', ['especie' => $code === 'dog' ? 'perro' : 'gato'])) ?>"><?= icon('scale', 'icon icon-sm') ?> Peso saludable</a></li>
                    <?php endif; ?>
                    <li><a href="<?= e(url('/herramientas/alimentos-peligrosos', ['mascota' => $code])) ?>"><?= icon('apple', 'icon icon-sm') ?> Alimentos peligrosos</a></li>
                </ul>
            </div>
        </aside>
    </div>
</div>
