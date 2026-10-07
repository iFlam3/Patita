<?php
/**
 * @var array<string, int> $petCounts
 */
?>
<div class="container page">
    <?= App\Core\View::partial('partials/breadcrumbs', ['items' => [['Mascotas', null]]]) ?>
    <header class="page-head">
        <div>
            <h1>Comprar por mascota</h1>
            <p class="page-intro">Elige a quién le estás comprando y te mostramos lo que tenemos para su especie.</p>
        </div>
    </header>

    <ul class="pet-grid pet-grid-large">
        <?php foreach (pets() as $code => $pet): ?>
            <?php $count = $petCounts[$code] ?? 0; ?>
            <li class="pet-card<?= isset($pet['subtypes']) ? ' pet-card-wide' : '' ?>">
                <a href="<?= e(url('/mascotas/' . $pet['slug'])) ?>">
                    <span class="pet-card-icon"><?= icon((string) $pet['icon']) ?></span>
                    <span class="pet-card-text">
                        <span class="pet-card-name"><?= e($pet['label']) ?></span>
                        <span class="pet-card-count"><?= $count === 1 ? '1 producto' : $count . ' productos' ?></span>
                        <span class="pet-card-sub"><?= e($pet['intro']) ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
